<?php

namespace Tests\Feature;

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
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class MonthlyReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_preview_maps_final_results_and_distinguishes_pending_from_not_scheduled(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createMasterData('Pemetaan');

        $this->createSchedule($context, $petugas, '2026-10-01', '08:00', [
            'end_time' => '08:30',
            'overall_status' => 'normal',
            'result_status' => 'sesuai',
        ]);
        $this->createSchedule($context, $petugas, '2026-10-02', '08:00');
        $this->createSchedule($context, $petugas, '2026-10-04', '08:00', [
            'end_time' => '08:45',
            'overall_status' => 'has_finding',
            'result_status' => 'tidak_sesuai',
        ]);
        $this->createSchedule($context, $petugas, '2026-10-05', '08:00', [
            'end_time' => null,
            'overall_status' => 'incomplete',
            'result_status' => 'sesuai',
        ]);

        $response = $this->actingAs($admin)->get(route('reports.monthly', [
            'machine_id' => $context['machine']->id,
            'month' => 10,
            'year' => 2026,
        ]));

        $response->assertOk()
            ->assertViewHas('report', function (mixed $report): bool {
                if (! is_array($report)) {
                    return false;
                }

                $cells = $report['groups'][0]['rows'][0]['cells'];

                return $cells[1]['state'] === 'conform'
                    && $cells[1]['symbol'] === '✓'
                    && $cells[2]['state'] === 'pending'
                    && $cells[2]['symbol'] === '—'
                    && $cells[3]['state'] === 'not_scheduled'
                    && $cells[3]['symbol'] === ''
                    && $cells[4]['state'] === 'nonconform'
                    && $cells[4]['symbol'] === '✕'
                    && $cells[5]['state'] === 'pending';
            });
    }

    public function test_latest_submitted_check_wins_when_the_same_item_has_multiple_checks_on_one_date(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createMasterData('Deterministik');
        $earlier = $this->createSchedule($context, $petugas, '2026-10-06', '08:00', [
            'end_time' => '08:30',
            'overall_status' => 'normal',
            'result_status' => 'sesuai',
        ]);
        $latest = $this->createSchedule($context, $petugas, '2026-10-06', '09:00', [
            'end_time' => '09:30',
            'overall_status' => 'has_finding',
            'result_status' => 'tidak_sesuai',
        ]);

        $response = $this->actingAs($admin)->get(route('reports.monthly', [
            'machine_id' => $context['machine']->id,
            'month' => 10,
            'year' => 2026,
        ]));

        $response->assertViewHas('report', function (mixed $report) use ($earlier, $latest): bool {
            if (! is_array($report)) {
                return false;
            }

            $cell = $report['groups'][0]['rows'][0]['cells'][6];

            return $cell['state'] === 'nonconform'
                && $cell['check_id'] === $latest['check']?->id
                && $cell['check_id'] !== $earlier['check']?->id;
        });
    }

    #[DataProvider('calendarMonths')]
    public function test_monthly_preview_uses_the_correct_calendar_length(int $month, int $year, int $expectedDays): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $context = $this->createMasterData('Kalender-'.$month.'-'.$year);

        $response = $this->actingAs($admin)->get(route('reports.monthly', [
            'machine_id' => $context['machine']->id,
            'month' => $month,
            'year' => $year,
        ]));

        $response->assertViewHas(
            'report',
            fn (mixed $report): bool => is_array($report) && $report['period']['days_in_month'] === $expectedDays,
        );
    }

    public function test_report_filters_reject_invalid_machine_month_and_year(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        foreach (['reports.monthly', 'mobile.report', 'reports.monthly.pdf', 'reports.monthly.excel'] as $routeName) {
            $this->actingAs($admin)
                ->from(route('reports.monthly'))
                ->get(route($routeName, [
                    'machine_id' => 999999,
                    'month' => 13,
                    'year' => 1999,
                ]))
                ->assertRedirect(route('reports.monthly'))
                ->assertSessionHasErrors(['machine_id', 'month', 'year']);
        }
    }

    public function test_mobile_report_displays_filter_validation_errors(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->followingRedirects()
            ->from(route('mobile.report'))
            ->get(route('mobile.report', ['month' => 13, 'year' => 2026]))
            ->assertOk()
            ->assertSee('class="mobile-report-validation"', false)
            ->assertSeeText('Bulan laporan harus antara Januari dan Desember.');
    }

    public function test_a_valid_selected_year_is_preserved_in_the_filter_options(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $context = $this->createMasterData('Tahun Filter');

        $this->actingAs($admin)->get(route('reports.monthly', [
            'machine_id' => $context['machine']->id,
            'month' => 2,
            'year' => 2100,
        ]))
            ->assertOk()
            ->assertSee('<option value="2100" selected>2100</option>', false);
    }

    public function test_petugas_preview_contains_only_own_results_and_assigned_pending_schedules(): void
    {
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $otherPetugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createMasterData('Akses');
        $this->createSchedule($context, $petugas, '2026-10-01', '08:00', [
            'end_time' => '08:30',
            'overall_status' => 'normal',
            'result_status' => 'sesuai',
        ]);
        $this->createSchedule($context, $petugas, '2026-10-02', '08:00');
        $this->createSchedule($context, $otherPetugas, '2026-10-03', '08:00', [
            'end_time' => '08:30',
            'overall_status' => 'has_finding',
            'result_status' => 'tidak_sesuai',
        ]);

        $response = $this->actingAs($petugas)->get(route('reports.monthly', [
            'machine_id' => $context['machine']->id,
            'month' => 10,
            'year' => 2026,
        ]));

        $response->assertOk()
            ->assertSee('href="'.route('reports.monthly').'" class="sidebar-menu__link', false)
            ->assertSee('data-mobile-route="report"', false)
            ->assertViewHas('report', function (mixed $report): bool {
                if (! is_array($report)) {
                    return false;
                }

                $cells = $report['groups'][0]['rows'][0]['cells'];

                return $cells[1]['state'] === 'conform'
                    && $cells[2]['state'] === 'pending'
                    && $cells[3]['state'] === 'not_scheduled';
            });
    }

    public function test_pdf_export_is_a_downloadable_pdf_built_from_the_selected_report(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createMasterData('PDF');
        $this->createSchedule($context, $petugas, '2026-10-01', '08:00', [
            'end_time' => '08:30',
            'overall_status' => 'normal',
            'result_status' => 'sesuai',
        ]);

        $response = $this->actingAs($admin)->get('/reports/monthly/pdf?'.http_build_query([
            'machine_id' => $context['machine']->id,
            'month' => 10,
            'year' => 2026,
        ]));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('laporan-maintenance-x-ray-pdf-2026-10.pdf', (string) $response->headers->get('content-disposition'));
    }

    public function test_excel_export_is_a_real_xlsx_with_matching_cells_and_user_text_is_not_a_formula(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createMasterData('Excel');
        $context['machine']->update(['equipment_name' => '=WEBSERVICE("https://invalid.test")']);
        $this->createSchedule($context, $petugas, '2026-10-01', '08:00', [
            'end_time' => '08:30',
            'overall_status' => 'normal',
            'result_status' => 'sesuai',
            'note' => '=1+1',
        ]);

        $response = $this->actingAs($admin)->get('/reports/monthly/excel?'.http_build_query([
            'machine_id' => $context['machine']->id,
            'month' => 10,
            'year' => 2026,
        ]));

        $response->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('content-disposition');
        $this->assertStringStartsWith('PK', $response->getContent());

        $temporaryPath = tempnam(sys_get_temp_dir(), 'monthly-report-test-');
        $this->assertNotFalse($temporaryPath);
        file_put_contents($temporaryPath, $response->getContent());
        $spreadsheet = IOFactory::load($temporaryPath);
        @unlink($temporaryPath);
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertSame('=WEBSERVICE("https://invalid.test")', $sheet->getCell('B4')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('B4')->getDataType());
        $this->assertSame('✓', $sheet->getCell('F10')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('AK10')->getDataType());
        $this->assertStringContainsString('=1+1', (string) $sheet->getCell('AK10')->getValue());
        $this->assertSame('F10', $sheet->getFreezePane());
    }

    public function test_excel_export_shows_a_clear_message_when_the_period_has_no_schedules(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $context = $this->createMasterData('Excel Kosong');

        $response = $this->actingAs($admin)->get(route('reports.monthly.excel', [
            'machine_id' => $context['machine']->id,
            'month' => 10,
            'year' => 2026,
        ]));

        $response->assertOk();
        $temporaryPath = tempnam(sys_get_temp_dir(), 'monthly-report-empty-');
        $this->assertNotFalse($temporaryPath);
        file_put_contents($temporaryPath, $response->getContent());
        $spreadsheet = IOFactory::load($temporaryPath);
        @unlink($temporaryPath);

        $this->assertSame(
            'Tidak ada jadwal maintenance untuk mesin dan periode yang dipilih.',
            $spreadsheet->getActiveSheet()->getCell('A10')->getValue(),
        );
    }

    public function test_petugas_can_export_a_report_within_their_scope(): void
    {
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createMasterData('Export Petugas');
        $this->createSchedule($context, $petugas, '2026-10-01', '08:00', [
            'end_time' => '08:30',
            'overall_status' => 'normal',
            'result_status' => 'sesuai',
        ]);

        $this->actingAs($petugas)->get(route('reports.monthly.excel', [
            'machine_id' => $context['machine']->id,
            'month' => 10,
            'year' => 2026,
        ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_supervisor_can_export_reports_for_all_machines(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createMasterData('Export Supervisor');
        $this->createSchedule($context, $petugas, '2026-10-01', '08:00', [
            'end_time' => '08:30',
            'overall_status' => 'normal',
            'result_status' => 'sesuai',
        ]);
        $filters = [
            'machine_id' => $context['machine']->id,
            'month' => 10,
            'year' => 2026,
        ];

        $this->actingAs($supervisor)->get(route('reports.monthly.pdf', $filters))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->actingAs($supervisor)->get(route('reports.monthly.excel', $filters))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_petugas_cannot_preview_or_export_a_machine_outside_their_scope(): void
    {
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $otherPetugas = User::factory()->create(['role' => UserRole::Petugas]);
        $context = $this->createMasterData('Milik Orang Lain');
        $this->createSchedule($context, $otherPetugas, '2026-10-01', '08:00', [
            'end_time' => '08:30',
            'overall_status' => 'normal',
            'result_status' => 'sesuai',
        ]);
        $query = http_build_query([
            'machine_id' => $context['machine']->id,
            'month' => 10,
            'year' => 2026,
        ]);

        $this->actingAs($petugas)->get('/reports/monthly?'.$query)->assertNotFound();
        $this->actingAs($petugas)->get('/reports/monthly/pdf?'.$query)->assertNotFound();
        $this->actingAs($petugas)->get('/reports/monthly/excel?'.$query)->assertNotFound();
    }

    public function test_desktop_and_mobile_previews_render_the_same_database_report_and_real_download_links(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $supervisor = User::factory()->create([
            'name' => 'Siti Supervisor Aktual',
            'role' => UserRole::Supervisor,
        ]);
        $petugas = User::factory()->create([
            'name' => 'Budi Petugas Aktual',
            'role' => UserRole::Petugas,
        ]);
        $context = $this->createMasterData('Database Preview');
        $result = $this->createSchedule($context, $petugas, '2026-10-01', '08:00', [
            'end_time' => '08:30',
            'overall_status' => 'has_finding',
            'result_status' => 'tidak_sesuai',
            'verification_status' => 'verified',
            'verified_by' => $supervisor->id,
            'verified_at' => '2026-10-01 10:00:00',
            'supervisor_note' => 'Disetujui setelah tindak lanjut.',
            'note' => 'Belt perlu dikencangkan.',
        ]);
        MaintenanceFinding::query()->create([
            'maintenance_check_id' => $result['check']->id,
            'maintenance_check_detail_id' => $result['detail']->id,
            'machine_id' => $context['machine']->id,
            'maintenance_item_id' => $context['item']->id,
            'reported_by' => $petugas->id,
            'finding_date' => '2026-10-01',
            'finding_description' => 'Conveyor tidak stabil.',
            'follow_up' => 'Kencangkan motor conveyor.',
            'photo' => null,
            'status' => 'resolved',
            'reviewed_by' => $supervisor->id,
            'reviewed_at' => '2026-10-01 09:45:00',
            'resolved_at' => '2026-10-01 09:50:00',
        ]);
        $filters = [
            'machine_id' => $context['machine']->id,
            'month' => 10,
            'year' => 2026,
        ];
        $pdfUrl = route('reports.monthly.pdf', $filters);
        $excelUrl = route('reports.monthly.excel', $filters);

        foreach ([route('reports.monthly', $filters), route('mobile.report', $filters)] as $url) {
            $this->actingAs($admin)->get($url)
                ->assertOk()
                ->assertSeeText('X-Ray Database Preview')
                ->assertSeeText('Lead curtain Database Preview')
                ->assertSeeText('Belt perlu dikencangkan.')
                ->assertSeeText('Conveyor tidak stabil.')
                ->assertSeeText('Siti Supervisor Aktual')
                ->assertSeeText('01-10-2026 10:00')
                ->assertSee('✕', false)
                ->assertSee($pdfUrl)
                ->assertSee($excelUrl);
        }
    }

    public function test_preview_shows_a_clear_empty_state_when_the_period_has_no_schedules(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $context = $this->createMasterData('Tanpa Jadwal');

        $this->actingAs($admin)->get(route('reports.monthly', [
            'machine_id' => $context['machine']->id,
            'month' => 10,
            'year' => 2026,
        ]))
            ->assertOk()
            ->assertSeeText('Tidak ada jadwal maintenance untuk mesin dan periode yang dipilih.');
    }

    /**
     * @return array<string, array{int, int, int}>
     */
    public static function calendarMonths(): array
    {
        return [
            'February common year' => [2, 2025, 28],
            'February leap year' => [2, 2028, 29],
            'April' => [4, 2026, 30],
            'October' => [10, 2026, 31],
        ];
    }

    /**
     * @return array{machine: Machine, category: MaintenanceCategory, item: MaintenanceItem}
     */
    private function createMasterData(string $suffix): array
    {
        $unique = fake()->unique()->numberBetween(1000, 999999);
        $machine = Machine::query()->create([
            'equipment_name' => 'X-Ray '.$suffix,
            'brand' => 'Smiths Detection',
            'type' => 'HI-SCAN',
            'serial_number' => 'REPORT-'.$unique,
            'location' => 'Terminal '.$unique,
            'airport' => 'UPBU Malikussaleh',
            'status' => 'active',
            'description' => null,
        ]);
        $category = MaintenanceCategory::query()->create([
            'name' => 'Kategori '.$suffix.' '.$unique,
            'frequency' => 'daily',
            'description' => null,
            'status' => 'active',
        ]);
        $item = MaintenanceItem::query()->create([
            'maintenance_category_id' => $category->id,
            'item_name' => 'Lead curtain '.$suffix,
            'criteria' => 'Tidak robek dan menutup sempurna.',
            'input_type' => 'sesuai_tidak_sesuai',
            'sort_order' => 1,
            'status' => 'active',
        ]);

        return compact('machine', 'category', 'item');
    }

    /**
     * @param  array{machine: Machine, category: MaintenanceCategory, item: MaintenanceItem}  $context
     * @param  array{end_time?: ?string, overall_status?: string, result_status?: string, note?: ?string, verification_status?: string, verified_by?: ?int, verified_at?: ?string, supervisor_note?: ?string}  $checkAttributes
     * @return array{schedule: MaintenanceSchedule, check: ?MaintenanceCheck, detail: ?MaintenanceCheckDetail}
     */
    private function createSchedule(
        array $context,
        User $petugas,
        string $date,
        string $time,
        array $checkAttributes = [],
    ): array {
        $schedule = MaintenanceSchedule::query()->create([
            'machine_id' => $context['machine']->id,
            'maintenance_category_id' => $context['category']->id,
            'scheduled_date' => $date,
            'scheduled_time' => $time,
            'assigned_user_id' => $petugas->id,
            'status' => isset($checkAttributes['end_time']) && $checkAttributes['end_time'] !== null ? 'completed' : 'scheduled',
            'notes' => null,
        ]);

        if ($checkAttributes === []) {
            return ['schedule' => $schedule, 'check' => null, 'detail' => null];
        }

        $check = MaintenanceCheck::query()->create([
            'maintenance_schedule_id' => $schedule->id,
            'machine_id' => $context['machine']->id,
            'checked_by' => $petugas->id,
            'verified_by' => $checkAttributes['verified_by'] ?? null,
            'check_date' => $date,
            'start_time' => $time,
            'end_time' => $checkAttributes['end_time'] ?? null,
            'overall_status' => $checkAttributes['overall_status'] ?? 'incomplete',
            'verification_status' => $checkAttributes['verification_status'] ?? 'waiting',
            'general_note' => null,
            'supervisor_note' => $checkAttributes['supervisor_note'] ?? null,
            'verified_at' => $checkAttributes['verified_at'] ?? null,
        ]);
        $detail = MaintenanceCheckDetail::query()->create([
            'maintenance_check_id' => $check->id,
            'maintenance_item_id' => $context['item']->id,
            'result_status' => $checkAttributes['result_status'] ?? 'belum_diperiksa',
            'actual_value' => null,
            'note' => $checkAttributes['note'] ?? null,
            'photo' => null,
        ]);

        return compact('schedule', 'check', 'detail');
    }
}
