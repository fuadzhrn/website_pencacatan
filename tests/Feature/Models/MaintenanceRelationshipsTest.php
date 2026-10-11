<?php

namespace Tests\Feature\Models;

use App\Models\Machine;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceCheck;
use App\Models\MaintenanceCheckDetail;
use App\Models\MaintenanceFinding;
use App\Models\MaintenanceItem;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MaintenanceRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_machine_returns_only_its_schedules_checks_and_findings(): void
    {
        $records = $this->createMaintenanceRecords();
        $this->createMaintenanceRecords('OTHER');

        $machine = $records['machine']->fresh();

        $this->assertSame([$records['schedule']->id], $machine->schedules->modelKeys());
        $this->assertSame([$records['check']->id], $machine->checks->modelKeys());
        $this->assertSame([$records['finding']->id], $machine->findings->modelKeys());
    }

    public function test_category_returns_only_its_items_and_schedules(): void
    {
        $records = $this->createMaintenanceRecords();
        $this->createMaintenanceRecords('OTHER');

        $category = $records['category']->fresh();

        $this->assertSame([$records['item']->id], $category->items->modelKeys());
        $this->assertSame([$records['schedule']->id], $category->schedules->modelKeys());
    }

    public function test_item_returns_its_category_check_details_and_findings(): void
    {
        $records = $this->createMaintenanceRecords();
        $this->createMaintenanceRecords('OTHER');

        $item = $records['item']->fresh();

        $this->assertTrue($item->category->is($records['category']));
        $this->assertSame([$records['detail']->id], $item->checkDetails->modelKeys());
        $this->assertSame([$records['finding']->id], $item->findings->modelKeys());
    }

    public function test_schedule_returns_its_machine_category_assignee_and_single_check(): void
    {
        $records = $this->createMaintenanceRecords();
        $this->createMaintenanceRecords('OTHER');

        $schedule = $records['schedule']->fresh();

        $this->assertTrue($schedule->machine->is($records['machine']));
        $this->assertTrue($schedule->category->is($records['category']));
        $this->assertTrue($schedule->assignedUser->is($records['assignee']));
        $this->assertTrue($schedule->check->is($records['check']));
    }

    public function test_check_returns_its_schedule_machine_actors_details_and_findings(): void
    {
        $records = $this->createMaintenanceRecords();
        $this->createMaintenanceRecords('OTHER');

        $check = $records['check']->fresh();

        $this->assertTrue($check->schedule->is($records['schedule']));
        $this->assertTrue($check->machine->is($records['machine']));
        $this->assertTrue($check->checkedBy->is($records['checker']));
        $this->assertTrue($check->verifiedBy->is($records['reviewer']));
        $this->assertSame([$records['detail']->id], $check->details->modelKeys());
        $this->assertSame([$records['finding']->id], $check->findings->modelKeys());
    }

    public function test_check_detail_returns_its_check_item_and_findings(): void
    {
        $records = $this->createMaintenanceRecords();
        $this->createMaintenanceRecords('OTHER');

        $detail = $records['detail']->fresh();

        $this->assertTrue($detail->check->is($records['check']));
        $this->assertTrue($detail->item->is($records['item']));
        $this->assertSame([$records['finding']->id], $detail->findings->modelKeys());
    }

    public function test_finding_returns_its_check_detail_machine_item_and_actors(): void
    {
        $records = $this->createMaintenanceRecords();

        $finding = $records['finding']->fresh();

        $this->assertTrue($finding->check->is($records['check']));
        $this->assertTrue($finding->checkDetail->is($records['detail']));
        $this->assertTrue($finding->machine->is($records['machine']));
        $this->assertTrue($finding->item->is($records['item']));
        $this->assertTrue($finding->reportedBy->is($records['checker']));
        $this->assertTrue($finding->reviewedBy->is($records['reviewer']));
    }

    public function test_users_return_only_the_records_for_their_actor_foreign_keys(): void
    {
        $records = $this->createMaintenanceRecords();
        $this->createMaintenanceRecords('OTHER');

        $assignee = $records['assignee']->fresh();
        $checker = $records['checker']->fresh();
        $reviewer = $records['reviewer']->fresh();

        $this->assertSame([$records['schedule']->id], $assignee->assignedMaintenanceSchedules->modelKeys());
        $this->assertSame([], $checker->assignedMaintenanceSchedules->modelKeys());
        $this->assertSame([$records['check']->id], $checker->performedMaintenanceChecks->modelKeys());
        $this->assertSame([], $reviewer->performedMaintenanceChecks->modelKeys());
        $this->assertSame([$records['check']->id], $reviewer->verifiedMaintenanceChecks->modelKeys());
        $this->assertSame([], $checker->verifiedMaintenanceChecks->modelKeys());
        $this->assertSame([$records['finding']->id], $checker->reportedMaintenanceFindings->modelKeys());
        $this->assertSame([], $reviewer->reportedMaintenanceFindings->modelKeys());
        $this->assertSame([$records['finding']->id], $reviewer->reviewedMaintenanceFindings->modelKeys());
        $this->assertSame([], $checker->reviewedMaintenanceFindings->modelKeys());
    }

    public function test_domain_dates_and_timestamps_are_carbon_instances(): void
    {
        $records = $this->createMaintenanceRecords();
        $dateAttributes = [
            'schedule' => ['scheduled_date' => '2026-10-11 00:00:00'],
            'check' => ['check_date' => '2026-10-11 00:00:00', 'verified_at' => '2026-10-11 10:00:00'],
            'finding' => [
                'finding_date' => '2026-10-11 00:00:00',
                'reviewed_at' => '2026-10-11 10:15:00',
                'resolved_at' => '2026-10-12 11:00:00',
            ],
        ];

        foreach ($dateAttributes as $name => $attributes) {
            $model = $records[$name]->fresh();

            foreach ($attributes as $attribute => $expected) {
                $this->assertInstanceOf(Carbon::class, $model->{$attribute});
                $this->assertSame($expected, $model->{$attribute}->toDateTimeString());
            }
        }

        foreach ($records as $model) {
            $this->assertInstanceOf(Carbon::class, $model->fresh()->created_at);
            $this->assertInstanceOf(Carbon::class, $model->fresh()->updated_at);
        }
    }

    public function test_deleting_optional_users_preserves_history_with_null_actor_relations(): void
    {
        $records = $this->createMaintenanceRecords();

        $records['assignee']->delete();
        $records['reviewer']->delete();

        $this->assertModelExists($records['schedule']);
        $this->assertNull($records['schedule']->fresh()->assignedUser);
        $this->assertModelExists($records['check']);
        $this->assertNull($records['check']->fresh()->verifiedBy);
        $this->assertModelExists($records['finding']);
        $this->assertNull($records['finding']->fresh()->reviewedBy);
        $this->assertTrue($records['check']->fresh()->checkedBy->is($records['checker']));
        $this->assertTrue($records['finding']->fresh()->reportedBy->is($records['checker']));
    }

    public function test_unassigned_schedule_and_unreviewed_finding_return_null_optional_relations(): void
    {
        $records = $this->createMaintenanceRecords();
        $schedule = MaintenanceSchedule::create([
            'machine_id' => $records['machine']->id,
            'maintenance_category_id' => $records['category']->id,
            'scheduled_date' => '2026-10-12',
        ]);
        $finding = MaintenanceFinding::create([
            'maintenance_check_id' => $records['check']->id,
            'machine_id' => $records['machine']->id,
            'reported_by' => $records['checker']->id,
            'finding_date' => '2026-10-12',
            'finding_description' => 'General finding without a checklist item',
        ]);
        $records['check']->update(['verified_by' => null, 'verified_at' => null]);

        $this->assertNull($schedule->fresh()->assignedUser);
        $this->assertNull($schedule->fresh()->check);
        $this->assertNull($records['check']->fresh()->verifiedBy);
        $this->assertNull($records['check']->fresh()->verified_at);
        $this->assertNull($finding->fresh()->checkDetail);
        $this->assertNull($finding->fresh()->item);
        $this->assertNull($finding->fresh()->reviewedBy);
        $this->assertNull($finding->fresh()->reviewed_at);
        $this->assertNull($finding->fresh()->resolved_at);
    }

    public function test_domain_attributes_survive_mass_assignment_without_assigning_identity(): void
    {
        $records = $this->createMaintenanceRecords();
        $expectedAttributes = [
            'machine' => [
                'equipment_name' => 'X-Ray Inspection System', 'brand' => 'Smiths Detection',
                'type' => 'HI-SCAN 6040', 'serial_number' => 'XRAY-001', 'location' => 'Terminal 1',
                'airport' => 'Soekarno-Hatta', 'status' => 'inactive', 'description' => 'Training machine',
            ],
            'category' => ['name' => 'Daily maintenance 001', 'frequency' => 'daily', 'description' => 'Daily checklist', 'status' => 'inactive'],
            'item' => ['item_name' => 'Check conveyor', 'criteria' => 'Conveyor operates normally', 'input_type' => 'nilai_aktual', 'sort_order' => 2, 'status' => 'inactive'],
            'schedule' => ['scheduled_time' => '08:00:00', 'status' => 'completed', 'notes' => 'Assigned inspection'],
            'check' => ['start_time' => '08:05:00', 'end_time' => '08:30:00', 'overall_status' => 'has_finding', 'verification_status' => 'verified', 'general_note' => 'Conveyor issue', 'supervisor_note' => 'Repair approved'],
            'detail' => ['result_status' => 'tidak_sesuai', 'actual_value' => '5', 'note' => 'Conveyor stops', 'photo' => 'checks/conveyor.jpg'],
            'finding' => ['finding_description' => 'Conveyor stops intermittently', 'follow_up' => 'Replace the belt', 'photo' => 'findings/conveyor.jpg', 'status' => 'resolved'],
        ];

        foreach ($expectedAttributes as $name => $attributes) {
            $model = $records[$name]->fresh();

            foreach ($attributes as $attribute => $expected) {
                $this->assertSame($expected, $model->{$attribute}, $name.'.'.$attribute);
            }

            $originalId = $model->id;
            $model->fill(['id' => 999999]);
            $this->assertSame($originalId, $model->id);
        }
    }

    public function test_user_accepts_phone_and_status_while_preserving_authentication_casts_and_hidden_fields(): void
    {
        $user = User::factory()->create();

        $user->update([
            'name' => 'Maintenance supervisor', 'email' => 'supervisor@example.com',
            'phone' => '+628123456789', 'status' => 'inactive',
            'role' => UserRole::Supervisor, 'password' => 'new-password',
        ]);
        $user = $user->fresh();

        $this->assertSame('Maintenance supervisor', $user->name);
        $this->assertSame('supervisor@example.com', $user->email);
        $this->assertSame('+628123456789', $user->phone);
        $this->assertSame('inactive', $user->status);
        $this->assertSame(UserRole::Supervisor, $user->role);
        $this->assertTrue(Hash::check('new-password', $user->password));
        $this->assertInstanceOf(Carbon::class, $user->email_verified_at);
        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->assertArrayNotHasKey('remember_token', $user->toArray());
    }

    /**
     * Separate table identities before creating children to expose swapped foreign keys.
     *
     * @return array{machine: Machine, category: MaintenanceCategory, item: MaintenanceItem, schedule: MaintenanceSchedule, check: MaintenanceCheck, detail: MaintenanceCheckDetail, finding: MaintenanceFinding, assignee: User, checker: User, reviewer: User}
     */
    private function createMaintenanceRecords(string $suffix = '001'): array
    {
        $assignee = User::factory()->create();
        $checker = User::factory()->create();
        $reviewer = User::factory()->create(['role' => UserRole::Supervisor]);
        $machine = Machine::create([
            'equipment_name' => 'X-Ray Inspection System', 'brand' => 'Smiths Detection',
            'type' => 'HI-SCAN 6040', 'serial_number' => 'XRAY-'.$suffix, 'location' => 'Terminal 1',
            'airport' => 'Soekarno-Hatta', 'status' => 'inactive', 'description' => 'Training machine',
        ]);
        $machine->id += 100;
        $machine->save();
        $category = MaintenanceCategory::create([
            'name' => 'Daily maintenance '.$suffix, 'frequency' => 'daily',
            'description' => 'Daily checklist', 'status' => 'inactive',
        ]);
        $category->id += 200;
        $category->save();
        $item = MaintenanceItem::create([
            'maintenance_category_id' => $category->id, 'item_name' => 'Check conveyor',
            'criteria' => 'Conveyor operates normally', 'input_type' => 'nilai_aktual',
            'sort_order' => 2, 'status' => 'inactive',
        ]);
        $item->id += 300;
        $item->save();
        $schedule = MaintenanceSchedule::create([
            'machine_id' => $machine->id, 'maintenance_category_id' => $category->id,
            'scheduled_date' => '2026-10-11', 'scheduled_time' => '08:00:00',
            'assigned_user_id' => $assignee->id, 'status' => 'completed', 'notes' => 'Assigned inspection',
        ]);
        $schedule->id += 400;
        $schedule->save();
        $check = MaintenanceCheck::create([
            'maintenance_schedule_id' => $schedule->id, 'machine_id' => $machine->id,
            'checked_by' => $checker->id, 'verified_by' => $reviewer->id,
            'check_date' => '2026-10-11', 'start_time' => '08:05:00', 'end_time' => '08:30:00',
            'overall_status' => 'has_finding', 'verification_status' => 'verified',
            'general_note' => 'Conveyor issue', 'supervisor_note' => 'Repair approved',
            'verified_at' => '2026-10-11 10:00:00',
        ]);
        $check->id += 500;
        $check->save();
        $detail = MaintenanceCheckDetail::create([
            'maintenance_check_id' => $check->id, 'maintenance_item_id' => $item->id,
            'result_status' => 'tidak_sesuai', 'actual_value' => '5', 'note' => 'Conveyor stops',
            'photo' => 'checks/conveyor.jpg',
        ]);
        $detail->id += 600;
        $detail->save();
        $finding = MaintenanceFinding::create([
            'maintenance_check_id' => $check->id, 'maintenance_check_detail_id' => $detail->id,
            'machine_id' => $machine->id, 'maintenance_item_id' => $item->id,
            'reported_by' => $checker->id, 'finding_date' => '2026-10-11',
            'finding_description' => 'Conveyor stops intermittently', 'follow_up' => 'Replace the belt',
            'photo' => 'findings/conveyor.jpg', 'status' => 'resolved', 'reviewed_by' => $reviewer->id,
            'reviewed_at' => '2026-10-11 10:15:00', 'resolved_at' => '2026-10-12 11:00:00',
        ]);
        $finding->id += 700;
        $finding->save();

        return compact('machine', 'category', 'item', 'schedule', 'check', 'detail', 'finding', 'assignee', 'checker', 'reviewer');
    }
}
