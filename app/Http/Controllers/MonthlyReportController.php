<?php

namespace App\Http\Controllers;

use App\Http\Requests\MonthlyReportRequest;
use App\Models\Machine;
use App\Models\User;
use App\Services\MonthlyReportExcelExporter;
use App\Services\MonthlyReportPdfExporter;
use App\Services\MonthlyReportService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MonthlyReportController extends Controller
{
    public function index(MonthlyReportRequest $request, MonthlyReportService $reportService): View
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();
        $machines = $reportService->visibleMachinesQuery($user)->get();
        $selectedMachine = $this->selectedMachine($machines, $validated['machine_id'] ?? null);
        $month = (int) $validated['month'];
        $year = (int) $validated['year'];
        $report = $selectedMachine
            ? $reportService->build($user, $selectedMachine, $month, $year)
            : null;
        $reportFilters = $selectedMachine ? [
            'machine_id' => $selectedMachine->id,
            'month' => $month,
            'year' => $year,
        ] : [];
        $initials = Str::of($user->name)
            ->trim()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $name): string => Str::upper(Str::substr($name, 0, 1)))
            ->implode('');

        return view('reports.monthly-database', [
            'activeMenu' => 'laporan',
            'pageTitle' => 'Laporan Bulanan',
            'pageDescription' => 'Rekap hasil preventive maintenance mesin X-Ray berdasarkan bulan dan tahun.',
            'userName' => $user->name,
            'userRole' => $user->role->label(),
            'userInitials' => $initials,
            'machines' => $machines,
            'selectedMachine' => $selectedMachine,
            'selectedMonth' => $month,
            'selectedYear' => $year,
            'monthOptions' => [
                1 => 'Januari',
                2 => 'Februari',
                3 => 'Maret',
                4 => 'April',
                5 => 'Mei',
                6 => 'Juni',
                7 => 'Juli',
                8 => 'Agustus',
                9 => 'September',
                10 => 'Oktober',
                11 => 'November',
                12 => 'Desember',
            ],
            'yearOptions' => collect(range(now()->year - 5, now()->year + 1))
                ->push($year)
                ->unique()
                ->sort()
                ->values()
                ->all(),
            'reportFilters' => $reportFilters,
            'report' => $report,
        ]);
    }

    public function pdf(
        MonthlyReportRequest $request,
        MonthlyReportService $reportService,
        MonthlyReportPdfExporter $pdfExporter,
    ): Response {
        $report = $this->resolveReport($request, $reportService);

        return response($pdfExporter->make($report), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->fileName($report, 'pdf').'"',
        ]);
    }

    public function excel(
        MonthlyReportRequest $request,
        MonthlyReportService $reportService,
        MonthlyReportExcelExporter $excelExporter,
    ): Response {
        $report = $this->resolveReport($request, $reportService);

        return response($excelExporter->make($report), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$this->fileName($report, 'xlsx').'"',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveReport(MonthlyReportRequest $request, MonthlyReportService $reportService): array
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();
        $machine = $this->selectedMachine(
            $reportService->visibleMachinesQuery($user)->get(),
            $validated['machine_id'] ?? null,
        ) ?? abort(404);

        return $reportService->build($user, $machine, (int) $validated['month'], (int) $validated['year']);
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function fileName(array $report, string $extension): string
    {
        $machineSlug = Str::slug($report['machine']->equipment_name);

        if ($machineSlug === '') {
            $machineSlug = 'mesin-'.$report['machine']->id;
        }

        return sprintf(
            'laporan-maintenance-%s-%04d-%02d.%s',
            $machineSlug,
            $report['period']['year'],
            $report['period']['month'],
            $extension,
        );
    }

    /**
     * @param  Collection<int, Machine>  $machines
     */
    private function selectedMachine(Collection $machines, mixed $machineId): ?Machine
    {
        if ($machineId === null) {
            return $machines->first();
        }

        return $machines->firstWhere('id', (int) $machineId) ?? abort(404);
    }
}
