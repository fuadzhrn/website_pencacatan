<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\MaintenanceCategoryController;
use App\Http\Controllers\MaintenanceFindingController;
use App\Http\Controllers\MaintenanceItemController;
use App\Http\Controllers\MaintenanceScheduleController;
use App\Http\Controllers\MonthlyReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/mobile/dashboard', [DashboardController::class, 'index'])->name('mobile.dashboard');

    Route::get('/schedules', [MaintenanceScheduleController::class, 'index'])
        ->middleware('role:admin,petugas,supervisor')
        ->name('schedules.index');
    Route::patch('/schedules/{schedule}/status', [MaintenanceScheduleController::class, 'updateStatus'])
        ->middleware('role:admin,petugas')
        ->name('schedules.update-status');
    Route::get('/schedules/{schedule}', [MaintenanceScheduleController::class, 'show'])
        ->middleware('role:admin,petugas,supervisor')
        ->whereNumber('schedule')
        ->name('schedules.show');

    Route::get('/inspections/today', [InspectionController::class, 'today'])
        ->middleware('role:admin,petugas,supervisor')
        ->name('inspections.today');
    Route::get('/inspections/schedules/{schedule}', [InspectionController::class, 'showSchedule'])
        ->middleware('role:admin,petugas,supervisor')
        ->whereNumber('schedule')
        ->name('inspections.schedules.show');
    Route::post('/inspections/schedules/{schedule}/start', [InspectionController::class, 'start'])
        ->middleware('role:petugas')
        ->whereNumber('schedule')
        ->name('inspections.start');
    Route::post('/inspections/schedules/{schedule}', [InspectionController::class, 'store'])
        ->middleware('role:petugas')
        ->whereNumber('schedule')
        ->name('inspections.store');

    Route::get('/inspections/history', [VerificationController::class, 'index'])
        ->middleware('role:admin,petugas,supervisor')
        ->name('inspections.history');
    Route::get('/inspections/history/{check}', [VerificationController::class, 'historyShow'])
        ->middleware('role:admin,petugas,supervisor')
        ->whereNumber('check')
        ->name('inspections.history.show');
    Route::get('/inspections/history/{check}/details/{detail}/photo', [VerificationController::class, 'detailPhoto'])
        ->middleware('role:admin,petugas,supervisor')
        ->whereNumber(['check', 'detail'])
        ->name('inspections.history.details.photo');
    Route::get('/inspections/history/{check}/findings/{finding}/photo', [VerificationController::class, 'findingPhoto'])
        ->middleware('role:admin,petugas,supervisor')
        ->whereNumber(['check', 'finding'])
        ->name('inspections.history.findings.photo');

    Route::get('/verifications', [VerificationController::class, 'index'])
        ->middleware('role:admin,supervisor')
        ->name('verifications.index');
    Route::get('/verifications/{check}', [VerificationController::class, 'show'])
        ->middleware('role:admin,supervisor')
        ->whereNumber('check')
        ->name('verifications.show');
    Route::patch('/verifications/{check}/verify', [VerificationController::class, 'verify'])
        ->middleware('role:supervisor')
        ->whereNumber('check')
        ->name('verifications.verify');
    Route::patch('/verifications/{check}/reject', [VerificationController::class, 'reject'])
        ->middleware('role:supervisor')
        ->whereNumber('check')
        ->name('verifications.reject');

    Route::get('/findings', [MaintenanceFindingController::class, 'index'])
        ->middleware('role:admin,petugas,supervisor')
        ->name('findings.index');
    Route::post('/findings', [MaintenanceFindingController::class, 'store'])
        ->middleware('role:petugas')
        ->name('findings.store');
    Route::patch('/findings/{finding}/status', [MaintenanceFindingController::class, 'updateStatus'])
        ->middleware('role:supervisor')
        ->whereNumber('finding')
        ->name('findings.update-status');
    Route::match(['put', 'patch'], '/findings/{finding}', [MaintenanceFindingController::class, 'update'])
        ->middleware('role:petugas')
        ->whereNumber('finding')
        ->name('findings.update');
    Route::get('/findings/{finding}', [MaintenanceFindingController::class, 'show'])
        ->middleware('role:admin,petugas,supervisor')
        ->whereNumber('finding')
        ->name('findings.show');
    Route::get('/findings/{finding}/photo', [MaintenanceFindingController::class, 'photo'])
        ->middleware('role:admin,petugas,supervisor')
        ->whereNumber('finding')
        ->name('findings.photo');

    Route::get('/reports/monthly', [MonthlyReportController::class, 'index'])
        ->middleware('role:admin,petugas,supervisor')
        ->name('reports.monthly');
    Route::get('/reports/monthly/pdf', [MonthlyReportController::class, 'pdf'])
        ->middleware('role:admin,petugas,supervisor')
        ->name('reports.monthly.pdf');
    Route::get('/reports/monthly/excel', [MonthlyReportController::class, 'excel'])
        ->middleware('role:admin,petugas,supervisor')
        ->name('reports.monthly.excel');

    Route::middleware('role:admin')->group(function (): void {
        Route::post('/schedules/generate', [MaintenanceScheduleController::class, 'generate'])
            ->name('schedules.generate');
        Route::resource('schedules', MaintenanceScheduleController::class)
            ->except(['index', 'show']);

        Route::get('/checklists', [MaintenanceCategoryController::class, 'index'])
            ->name('checklists.index');

        Route::patch('/maintenance-categories/{category}/toggle-status', [MaintenanceCategoryController::class, 'toggleStatus'])
            ->name('maintenance-categories.toggle-status');
        Route::resource('maintenance-categories', MaintenanceCategoryController::class)
            ->parameters(['maintenance-categories' => 'category'])
            ->only(['store', 'show', 'update', 'destroy']);

        Route::patch('/maintenance-items/{item}/toggle-status', [MaintenanceItemController::class, 'toggleStatus'])
            ->name('maintenance-items.toggle-status');
        Route::resource('maintenance-items', MaintenanceItemController::class)
            ->parameters(['maintenance-items' => 'item'])
            ->only(['store', 'show', 'update', 'destroy']);

        Route::patch('/machines/{machine}/toggle-status', [MachineController::class, 'toggleStatus'])
            ->name('machines.toggle-status');
        Route::resource('machines', MachineController::class);

        Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])
            ->name('users.toggle-status');
        Route::patch('/users/{user}/reset-password', [UserController::class, 'resetPassword'])
            ->name('users.reset-password');
        Route::resource('users', UserController::class);
    });

    Route::get('/mobile/report', [MonthlyReportController::class, 'index'])
        ->middleware('role:admin,petugas,supervisor')
        ->name('mobile.report');

    Route::get('/mobile/schedules', [MaintenanceScheduleController::class, 'index'])
        ->middleware('role:admin,petugas,supervisor')
        ->name('mobile.schedules');

    Route::get('/mobile/history', [VerificationController::class, 'index'])
        ->middleware('role:admin,petugas,supervisor')
        ->name('mobile.history');

    Route::get('/mobile/findings', [MaintenanceFindingController::class, 'index'])
        ->middleware('role:admin,petugas,supervisor')
        ->name('mobile.findings');

    Route::get('/mobile/inspection-today', [InspectionController::class, 'today'])
        ->middleware('role:admin,petugas,supervisor')
        ->name('mobile.inspection-today');

    Route::get('/mobile/users', [UserController::class, 'index'])
        ->middleware('role:admin')
        ->name('mobile.users');

    Route::get('/mobile/machines', [MachineController::class, 'index'])
        ->middleware('role:admin')
        ->name('mobile.machines');

    Route::get('/mobile/checklists', [MaintenanceCategoryController::class, 'index'])
        ->middleware('role:admin')
        ->name('mobile.checklists');

    Route::get('/inspections/{check}', [InspectionController::class, 'show'])
        ->middleware('role:admin,petugas,supervisor')
        ->whereNumber('check')
        ->name('inspections.show');
    Route::get('/inspections/{check}/details/{detail}/photo', [InspectionController::class, 'detailPhoto'])
        ->middleware('role:admin,petugas,supervisor')
        ->whereNumber(['check', 'detail'])
        ->name('inspections.details.photo');
    Route::get('/inspections/{check}/findings/{finding}/photo', [InspectionController::class, 'findingPhoto'])
        ->middleware('role:admin,petugas,supervisor')
        ->whereNumber(['check', 'finding'])
        ->name('inspections.findings.photo');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
