<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\MaintenanceCheck;
use App\Models\MaintenanceCheckDetail;
use App\Models\MaintenanceFinding;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\Services\MaintenanceScheduleService;
use App\Support\MaintenanceLabels;
use App\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(public MaintenanceScheduleService $scheduleService) {}

    public function index(Request $request): View
    {
        $this->scheduleService->markOverdue();

        /** @var User $user */
        $user = $request->user();
        $today = today()->toDateString();
        $monthStart = today()->startOfMonth()->toDateString();
        $monthEnd = today()->endOfMonth()->toDateString();

        $scheduleQuery = $this->visibleSchedules($user);
        $checkQuery = $this->visibleChecks($user);
        $findingQuery = $this->visibleFindings($user);
        $todayScheduleQuery = (clone $scheduleQuery)
            ->whereDate('scheduled_date', $today)
            ->where('status', '!=', 'cancelled');

        $totalToday = (clone $todayScheduleQuery)->count();
        $completedToday = (clone $todayScheduleQuery)->where('status', 'completed')->count();
        $statistics = [
            'total_machines' => $user->role === UserRole::Petugas
                ? (clone $scheduleQuery)->distinct()->count('machine_id')
                : Machine::query()->count(),
            'today_schedules' => $totalToday,
            'completed_today' => $completedToday,
            'pending_today' => (clone $todayScheduleQuery)->whereIn('status', ['scheduled', 'pending'])->count(),
            'overdue' => (clone $scheduleQuery)->where('status', 'overdue')->count(),
            'findings_open' => (clone $findingQuery)->where('status', 'open')->count(),
            'findings_review' => (clone $findingQuery)->where('status', 'review')->count(),
            'findings_resolved' => (clone $findingQuery)->where('status', 'resolved')->count(),
            'waiting_verification' => (clone $checkQuery)
                ->whereNotNull('end_time')
                ->where('verification_status', 'waiting')
                ->count(),
        ];

        $dailyProgress = [
            'total' => $totalToday,
            'completed' => $completedToday,
            'percentage' => $this->percentage($completedToday, $totalToday),
        ];

        $monthlyCheckQuery = (clone $checkQuery)->whereBetween('check_date', [$monthStart, $monthEnd]);
        $monthlyScheduleQuery = (clone $scheduleQuery)
            ->whereBetween('scheduled_date', [$monthStart, $monthEnd])
            ->where('status', '!=', 'cancelled');
        $monthlyFindingQuery = (clone $findingQuery)->whereBetween('finding_date', [$monthStart, $monthEnd]);
        $monthlyDetailQuery = MaintenanceCheckDetail::query()
            ->whereIn('maintenance_check_id', (clone $monthlyCheckQuery)->select('id'));
        $monthlyScheduleTotal = (clone $monthlyScheduleQuery)->count();
        $monthlyScheduleCompleted = (clone $monthlyScheduleQuery)->where('status', 'completed')->count();

        $monthlySummary = [
            'total_checks' => (clone $monthlyCheckQuery)->count(),
            'compliant_details' => (clone $monthlyDetailQuery)->where('result_status', 'sesuai')->count(),
            'noncompliant_details' => (clone $monthlyDetailQuery)->where('result_status', 'tidak_sesuai')->count(),
            'total_findings' => (clone $monthlyFindingQuery)->count(),
            'completion_percentage' => $this->percentage($monthlyScheduleCompleted, $monthlyScheduleTotal),
            'verified_checks' => (clone $monthlyCheckQuery)->where('verification_status', 'verified')->count(),
            'waiting_verification' => (clone $monthlyCheckQuery)
                ->whereNotNull('end_time')
                ->where('verification_status', 'waiting')
                ->count(),
        ];

        $todaySchedules = (clone $todayScheduleQuery)
            ->with(['machine', 'maintenanceCategory', 'assignedUser', 'check'])
            ->orderBy('scheduled_time')
            ->orderBy('id')
            ->limit(5)
            ->get();

        $latestFindings = (clone $findingQuery)
            ->with(['machine', 'item'])
            ->latest('finding_date')
            ->latest('id')
            ->limit(5)
            ->get();

        return view('dashboard', [
            ...$this->layoutData($user),
            'statistics' => $statistics,
            'dailyProgress' => $dailyProgress,
            'todaySchedules' => $todaySchedules,
            'latestFindings' => $latestFindings,
            'monthlySummary' => $monthlySummary,
            'todayIso' => $today,
            'todayLabel' => today()->locale('id')->translatedFormat('l, d F Y'),
            'monthLabel' => today()->locale('id')->translatedFormat('F Y'),
            'updatedAtLabel' => now()->format('H.i').' WITA',
            'scheduleStatusLabels' => $this->scheduleStatusLabels(),
            'checkStatusLabels' => $this->checkStatusLabels(),
            'findingStatusLabels' => $this->findingStatusLabels(),
            'frequencyLabels' => $this->frequencyLabels(),
            'findingActiveCount' => $statistics['findings_open'] + $statistics['findings_review'],
        ]);
    }

    /** @return Builder<MaintenanceSchedule> */
    private function visibleSchedules(User $user): Builder
    {
        return MaintenanceSchedule::query()->when(
            $user->role === UserRole::Petugas,
            fn (Builder $query): Builder => $query->where('assigned_user_id', $user->id),
        );
    }

    /** @return Builder<MaintenanceCheck> */
    private function visibleChecks(User $user): Builder
    {
        return MaintenanceCheck::query()->when(
            $user->role === UserRole::Petugas,
            fn (Builder $query): Builder => $query->where('checked_by', $user->id),
        );
    }

    /** @return Builder<MaintenanceFinding> */
    private function visibleFindings(User $user): Builder
    {
        return MaintenanceFinding::query()->when(
            $user->role === UserRole::Petugas,
            fn (Builder $query): Builder => $query->where('reported_by', $user->id),
        );
    }

    private function percentage(int $completed, int $total): int
    {
        return $total > 0 ? (int) round(($completed / $total) * 100) : 0;
    }

    /** @return array<string, string> */
    private function layoutData(User $user): array
    {
        $initials = Str::of($user->name)
            ->trim()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $name): string => Str::upper(Str::substr($name, 0, 1)))
            ->implode('');

        return [
            'activeMenu' => 'dashboard',
            'pageTitle' => 'Dashboard',
            'pageDescription' => 'Pantau aktivitas preventive maintenance hari ini.',
            'userName' => $user->name,
            'userRole' => $user->role->label(),
            'userInitials' => $initials,
        ];
    }

    /** @return array<string, string> */
    private function scheduleStatusLabels(): array
    {
        return MaintenanceLabels::scheduleStatuses();
    }

    /** @return array<string, string> */
    private function checkStatusLabels(): array
    {
        return MaintenanceLabels::checkResults();
    }

    /** @return array<string, string> */
    private function findingStatusLabels(): array
    {
        return MaintenanceLabels::findingStatuses();
    }

    /** @return array<string, string> */
    private function frequencyLabels(): array
    {
        return MaintenanceLabels::frequencies();
    }
}
