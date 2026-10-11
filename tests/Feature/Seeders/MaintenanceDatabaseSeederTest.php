<?php

namespace Tests\Feature\Seeders;

use App\Models\Machine;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceCheck;
use App\Models\MaintenanceCheckDetail;
use App\Models\MaintenanceFinding;
use App\Models\MaintenanceItem;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\UserRole;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MaintenanceDatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-11 08:00:00'));
        config()->set('maintenance.initial_admin', [
            'name' => 'Configured Administrator', 'email' => 'configured-admin@example.com',
            'password' => 'ConfiguredPassword123!',
        ]);
    }

    public function test_default_seeding_creates_three_active_accounts_with_their_roles_and_password(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 3);
        foreach ([
            'admin@example.com' => UserRole::Admin,
            'petugas@example.com' => UserRole::Petugas,
            'supervisor@example.com' => UserRole::Supervisor,
        ] as $email => $role) {
            $user = User::query()->where('email', $email)->firstOrFail();

            $this->assertSame($role, $user->role);
            $this->assertSame('active', $user->status);
            $this->assertTrue(Hash::check('password', $user->password));
        }
        $this->assertDatabaseMissing('users', ['email' => 'configured-admin@example.com']);
    }

    public function test_master_data_contains_three_machines_and_items_for_every_required_frequency(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('machines', 3);
        $this->assertDatabaseCount('maintenance_categories', 6);
        foreach ([
            'Harian' => 'daily', 'Mingguan' => 'weekly', 'Bulanan' => 'monthly',
            'Triwulan' => 'quarterly', 'Semesteran' => 'semester', 'Tahunan' => 'yearly',
        ] as $name => $frequency) {
            $category = MaintenanceCategory::query()->where('name', $name)->firstOrFail();

            $this->assertSame($frequency, $category->frequency);
            $this->assertNotEmpty($category->items);
            foreach ($category->items as $item) {
                $this->assertSame($category->id, $item->maintenance_category_id);
                $this->assertContains($item->input_type, ['sesuai_tidak_sesuai', 'nilai_aktual', 'catatan']);
            }
        }
    }

    public function test_october_transactions_have_coherent_parents_actors_and_checklist_results(): void
    {
        $this->seed(DatabaseSeeder::class);

        $schedules = MaintenanceSchedule::with(['machine', 'category', 'assignedUser'])->get();
        $checks = MaintenanceCheck::with(['schedule.category.items', 'machine', 'checkedBy', 'verifiedBy', 'details.item', 'findings'])->get();

        $this->assertGreaterThan(1, $schedules->count());
        $this->assertGreaterThan(1, $checks->count());
        $this->assertSame(['has_finding', 'normal'], $checks->pluck('overall_status')->unique()->sort()->values()->all());
        foreach ($schedules as $schedule) {
            $this->assertModelExists($schedule->machine);
            $this->assertModelExists($schedule->category);
            $this->assertSame(UserRole::Petugas, $schedule->assignedUser->role);
            $this->assertSame('2026-10', $schedule->scheduled_date->format('Y-m'));
        }
        foreach ($checks as $check) {
            $this->assertTrue($check->machine->is($check->schedule->machine));
            $this->assertTrue($check->checkedBy->is($check->schedule->assignedUser));
            $this->assertSame('completed', $check->schedule->status);
            $this->assertSame($check->schedule->scheduled_date->toDateString(), $check->check_date->toDateString());
            $this->assertLessThan($check->end_time, $check->start_time);
            $this->assertNotEmpty($check->details);
            $this->assertSame(
                $check->schedule->category->items->sortBy('id')->values()->modelKeys(),
                $check->details->pluck('maintenance_item_id')->sort()->values()->all(),
            );
            foreach ($check->details as $detail) {
                $this->assertSame($check->id, $detail->maintenance_check_id);
                $this->assertSame($check->schedule->maintenance_category_id, $detail->item->maintenance_category_id);
                $this->assertContains($detail->result_status, ['sesuai', 'tidak_sesuai']);
            }
        }
        $normalCheck = $checks->firstWhere('overall_status', 'normal');
        $this->assertSame(['sesuai'], $normalCheck->details->pluck('result_status')->unique()->all());
        $this->assertEmpty($normalCheck->findings);
        $this->assertSame('verified', $normalCheck->verification_status);
        $this->assertSame(UserRole::Supervisor, $normalCheck->verifiedBy->role);
        $this->assertSame('2026-10', $normalCheck->verified_at->format('Y-m'));
    }

    public function test_findings_cover_open_review_and_resolved_with_consistent_history(): void
    {
        $this->seed(DatabaseSeeder::class);

        $findings = MaintenanceFinding::with(['check.checkedBy', 'checkDetail', 'machine', 'item', 'reportedBy', 'reviewedBy'])->get();

        $this->assertSame(['open', 'resolved', 'review'], $findings->pluck('status')->unique()->sort()->values()->all());
        foreach ($findings as $finding) {
            $this->assertSame('has_finding', $finding->check->overall_status);
            $this->assertSame('tidak_sesuai', $finding->checkDetail->result_status);
            $this->assertSame($finding->check->id, $finding->checkDetail->maintenance_check_id);
            $this->assertTrue($finding->machine->is($finding->check->machine));
            $this->assertSame($finding->item->id, $finding->checkDetail->maintenance_item_id);
            $this->assertTrue($finding->reportedBy->is($finding->check->checkedBy));
            $this->assertSame($finding->check->check_date->toDateString(), $finding->finding_date->toDateString());
            $this->assertNotEmpty($finding->finding_description);
        }
        $open = $findings->firstWhere('status', 'open');
        $this->assertNull($open->reviewedBy);
        $this->assertNull($open->reviewed_at);
        $this->assertNull($open->resolved_at);
        foreach (['review', 'resolved'] as $status) {
            $finding = $findings->firstWhere('status', $status);

            $this->assertSame(UserRole::Supervisor, $finding->reviewedBy->role);
            $this->assertSame('2026-10', $finding->reviewed_at->format('Y-m'));
            $this->assertGreaterThanOrEqual($finding->finding_date, $finding->reviewed_at);
            $this->assertNotEmpty($finding->follow_up);
        }
        $this->assertNull($findings->firstWhere('status', 'review')->resolved_at);
        $resolved = $findings->firstWhere('status', 'resolved');
        $this->assertSame('2026-10', $resolved->resolved_at->format('Y-m'));
        $this->assertGreaterThanOrEqual($resolved->reviewed_at, $resolved->resolved_at);
    }

    public function test_repeated_default_seeding_preserves_all_counts_and_identities_on_another_day(): void
    {
        $this->seed(DatabaseSeeder::class);
        $models = [
            User::class => 3, Machine::class => 3, MaintenanceCategory::class => 6, MaintenanceItem::class => 12,
            MaintenanceSchedule::class => 7, MaintenanceCheck::class => 4,
            MaintenanceCheckDetail::class => 8, MaintenanceFinding::class => 3,
        ];
        $identities = [];

        foreach ($models as $model => $count) {
            $identities[$model] = $model::query()->orderBy('id')->pluck('id')->all();
            $this->assertDatabaseCount((new $model)->getTable(), $count);
        }

        $this->travelTo(Carbon::parse('2026-10-20 08:00:00'));
        $this->seed(DatabaseSeeder::class);

        foreach ($models as $model => $count) {
            $this->assertDatabaseCount((new $model)->getTable(), count($identities[$model]));
            $this->assertSame($identities[$model], $model::query()->orderBy('id')->pluck('id')->all());
        }
    }
}
