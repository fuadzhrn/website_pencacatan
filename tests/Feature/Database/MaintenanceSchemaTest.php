<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MaintenanceSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_maintenance_schema_contains_the_required_columns_without_day_columns(): void
    {
        $requiredColumns = [
            'users' => ['id', 'name', 'email', 'email_verified_at', 'password', 'role', 'phone', 'status', 'remember_token', 'created_at', 'updated_at'],
            'machines' => ['id', 'equipment_name', 'brand', 'type', 'serial_number', 'location', 'airport', 'status', 'description', 'created_at', 'updated_at'],
            'maintenance_categories' => ['id', 'name', 'frequency', 'description', 'status', 'created_at', 'updated_at'],
            'maintenance_items' => ['id', 'maintenance_category_id', 'item_name', 'criteria', 'input_type', 'sort_order', 'status', 'created_at', 'updated_at'],
            'maintenance_schedules' => ['id', 'machine_id', 'maintenance_category_id', 'scheduled_date', 'scheduled_time', 'assigned_user_id', 'status', 'notes', 'created_at', 'updated_at'],
            'maintenance_checks' => ['id', 'maintenance_schedule_id', 'machine_id', 'checked_by', 'verified_by', 'check_date', 'start_time', 'end_time', 'overall_status', 'verification_status', 'general_note', 'supervisor_note', 'verified_at', 'created_at', 'updated_at'],
            'maintenance_check_details' => ['id', 'maintenance_check_id', 'maintenance_item_id', 'result_status', 'actual_value', 'note', 'photo', 'created_at', 'updated_at'],
            'maintenance_findings' => ['id', 'maintenance_check_id', 'maintenance_check_detail_id', 'machine_id', 'maintenance_item_id', 'reported_by', 'finding_date', 'finding_description', 'follow_up', 'photo', 'status', 'reviewed_by', 'reviewed_at', 'resolved_at', 'created_at', 'updated_at'],
        ];

        foreach ($requiredColumns as $table => $columns) {
            $actualColumns = Schema::getColumnListing($table);

            foreach ($columns as $column) {
                $this->assertContains($column, $actualColumns, "Missing {$table}.{$column}");
            }

            for ($day = 1; $day <= 31; $day++) {
                $this->assertNotContains("tanggal_{$day}", $actualColumns, $table);
            }
        }

        $requiredIndexes = [
            'users' => [['role'], ['status']],
            'machines' => [['status']],
            'maintenance_categories' => [['frequency'], ['status']],
            'maintenance_items' => [['status']],
            'maintenance_schedules' => [['scheduled_date', 'status']],
            'maintenance_findings' => [['status', 'finding_date']],
        ];

        foreach ($requiredIndexes as $table => $indexes) {
            foreach ($indexes as $columns) {
                $this->assertTrue(Schema::hasIndex($table, $columns), $table.': '.implode(', ', $columns));
            }
        }

        $requiredUniqueIndexes = [
            'machines' => [['serial_number']],
            'maintenance_categories' => [['name']],
            'maintenance_items' => [['maintenance_category_id', 'item_name']],
            'maintenance_checks' => [['maintenance_schedule_id']],
            'maintenance_check_details' => [['maintenance_check_id', 'maintenance_item_id']],
        ];

        foreach ($requiredUniqueIndexes as $table => $indexes) {
            foreach ($indexes as $columns) {
                $this->assertTrue(Schema::hasIndex($table, $columns, 'unique'), $table.': '.implode(', ', $columns));
            }
        }
    }

    public function test_maintenance_schema_uses_mysql_compatible_identifier_lengths(): void
    {
        $tables = [
            'machines',
            'maintenance_categories',
            'maintenance_items',
            'maintenance_schedules',
            'maintenance_checks',
            'maintenance_check_details',
            'maintenance_findings',
        ];

        foreach ($tables as $table) {
            foreach (Schema::getIndexes($table) as $index) {
                $this->assertLessThanOrEqual(64, strlen($index['name']), $index['name']);
            }
        }
    }

    public function test_a_schedule_can_have_only_one_maintenance_check(): void
    {
        $scheduleId = $this->createSchedule();
        $checker = User::factory()->create();
        $check = [
            'maintenance_schedule_id' => $scheduleId,
            'machine_id' => DB::table('maintenance_schedules')->where('id', $scheduleId)->value('machine_id'),
            'checked_by' => $checker->id,
            'check_date' => '2026-10-11',
        ];
        DB::table('maintenance_checks')->insert($check);

        $this->expectException(UniqueConstraintViolationException::class);

        DB::table('maintenance_checks')->insert($check);
    }

    public function test_a_check_can_contain_an_item_only_once(): void
    {
        $scheduleId = $this->createSchedule();
        $schedule = DB::table('maintenance_schedules')->find($scheduleId);
        $checker = User::factory()->create();
        $checkId = DB::table('maintenance_checks')->insertGetId([
            'maintenance_schedule_id' => $scheduleId,
            'machine_id' => $schedule->machine_id,
            'checked_by' => $checker->id,
            'check_date' => '2026-10-11',
        ]);
        $itemId = DB::table('maintenance_items')->insertGetId([
            'maintenance_category_id' => $schedule->maintenance_category_id,
            'item_name' => 'Check conveyor',
            'criteria' => 'Conveyor operates normally',
            'input_type' => 'sesuai_tidak_sesuai',
        ]);
        $detail = ['maintenance_check_id' => $checkId, 'maintenance_item_id' => $itemId];
        DB::table('maintenance_check_details')->insert($detail);

        $this->expectException(UniqueConstraintViolationException::class);

        DB::table('maintenance_check_details')->insert($detail);
    }

    public function test_deleting_an_optional_assignee_preserves_the_schedule(): void
    {
        $assignee = User::factory()->create();
        $scheduleId = $this->createSchedule($assignee->id);

        $assignee->delete();

        $this->assertDatabaseHas('maintenance_schedules', [
            'id' => $scheduleId,
            'assigned_user_id' => null,
        ]);
    }

    public function test_deleting_a_schedule_with_a_check_is_rejected_and_preserves_history(): void
    {
        $scheduleId = $this->createSchedule();
        $checkId = $this->createCheck($scheduleId);

        try {
            DB::table('maintenance_schedules')->where('id', $scheduleId)->delete();
            $this->fail('A schedule with a historical check must not be deleted.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('foreign key', strtolower($exception->getMessage()));
        }

        $this->assertDatabaseHas('maintenance_schedules', ['id' => $scheduleId]);
        $this->assertDatabaseHas('maintenance_checks', [
            'id' => $checkId,
            'maintenance_schedule_id' => $scheduleId,
        ]);
    }

    public function test_deleting_a_check_without_findings_cascades_its_details(): void
    {
        $scheduleId = $this->createSchedule();
        $checkId = $this->createCheck($scheduleId);
        $categoryId = DB::table('maintenance_schedules')->where('id', $scheduleId)->value('maintenance_category_id');
        $itemId = $this->createItem($categoryId);
        $detailId = DB::table('maintenance_check_details')->insertGetId([
            'maintenance_check_id' => $checkId,
            'maintenance_item_id' => $itemId,
        ]);

        DB::table('maintenance_checks')->where('id', $checkId)->delete();

        $this->assertDatabaseMissing('maintenance_checks', ['id' => $checkId]);
        $this->assertDatabaseMissing('maintenance_check_details', ['id' => $detailId]);
        $this->assertDatabaseHas('maintenance_schedules', ['id' => $scheduleId]);
        $this->assertDatabaseHas('maintenance_items', ['id' => $itemId]);
    }

    public function test_deleting_a_referenced_check_detail_preserves_the_finding_with_a_null_detail(): void
    {
        $scheduleId = $this->createSchedule();
        $checkId = $this->createCheck($scheduleId);
        $categoryId = DB::table('maintenance_schedules')->where('id', $scheduleId)->value('maintenance_category_id');
        $itemId = $this->createItem($categoryId);
        $detailId = DB::table('maintenance_check_details')->insertGetId([
            'maintenance_check_id' => $checkId,
            'maintenance_item_id' => $itemId,
        ]);
        $findingId = $this->createFinding($checkId, $detailId, $itemId);

        DB::table('maintenance_check_details')->where('id', $detailId)->delete();

        $this->assertDatabaseMissing('maintenance_check_details', ['id' => $detailId]);
        $this->assertDatabaseHas('maintenance_findings', [
            'id' => $findingId,
            'maintenance_check_id' => $checkId,
            'maintenance_check_detail_id' => null,
            'maintenance_item_id' => $itemId,
            'finding_description' => 'Conveyor stops intermittently',
        ]);
        $this->assertDatabaseHas('maintenance_checks', ['id' => $checkId]);
        $this->assertDatabaseHas('maintenance_items', ['id' => $itemId]);
    }

    public function test_deleting_an_independently_referenced_item_preserves_the_finding_with_a_null_item(): void
    {
        $scheduleId = $this->createSchedule();
        $checkId = $this->createCheck($scheduleId);
        $categoryId = DB::table('maintenance_schedules')->where('id', $scheduleId)->value('maintenance_category_id');
        $itemId = $this->createItem($categoryId);
        $findingId = $this->createFinding($checkId, itemId: $itemId);

        DB::table('maintenance_items')->where('id', $itemId)->delete();

        $this->assertDatabaseMissing('maintenance_items', ['id' => $itemId]);
        $this->assertDatabaseHas('maintenance_findings', [
            'id' => $findingId,
            'maintenance_check_id' => $checkId,
            'maintenance_check_detail_id' => null,
            'maintenance_item_id' => null,
            'finding_description' => 'Conveyor stops intermittently',
        ]);
        $this->assertDatabaseHas('maintenance_checks', ['id' => $checkId]);
        $this->assertDatabaseHas('maintenance_categories', ['id' => $categoryId]);
    }

    private function createCheck(int $scheduleId): int
    {
        $machineId = DB::table('maintenance_schedules')->where('id', $scheduleId)->value('machine_id');

        return DB::table('maintenance_checks')->insertGetId([
            'maintenance_schedule_id' => $scheduleId,
            'machine_id' => $machineId,
            'checked_by' => User::factory()->create()->id,
            'check_date' => '2026-10-11',
        ]);
    }

    private function createItem(int $categoryId): int
    {
        return DB::table('maintenance_items')->insertGetId([
            'maintenance_category_id' => $categoryId,
            'item_name' => 'Check conveyor',
            'criteria' => 'Conveyor operates normally',
            'input_type' => 'sesuai_tidak_sesuai',
        ]);
    }

    private function createFinding(int $checkId, ?int $detailId = null, ?int $itemId = null): int
    {
        $check = DB::table('maintenance_checks')->find($checkId);

        return DB::table('maintenance_findings')->insertGetId([
            'maintenance_check_id' => $checkId,
            'maintenance_check_detail_id' => $detailId,
            'machine_id' => $check->machine_id,
            'maintenance_item_id' => $itemId,
            'reported_by' => $check->checked_by,
            'finding_date' => '2026-10-11',
            'finding_description' => 'Conveyor stops intermittently',
        ]);
    }

    private function createSchedule(?int $assignedUserId = null): int
    {
        $machineId = DB::table('machines')->insertGetId([
            'equipment_name' => 'X-Ray Inspection System',
            'brand' => 'Smiths Detection',
            'type' => 'HI-SCAN 6040',
            'serial_number' => 'XRAY-001',
            'location' => 'Terminal 1',
            'airport' => 'Soekarno-Hatta',
        ]);
        $categoryId = DB::table('maintenance_categories')->insertGetId([
            'name' => 'Daily maintenance',
            'frequency' => 'daily',
        ]);

        return DB::table('maintenance_schedules')->insertGetId([
            'machine_id' => $machineId,
            'maintenance_category_id' => $categoryId,
            'scheduled_date' => '2026-10-11',
            'assigned_user_id' => $assignedUserId,
        ]);
    }
}
