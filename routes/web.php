<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\View\View;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/machines', function (Request $request): View {
        $user = $request->user();
        $initials = Str::of($user->name)
            ->trim()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $name): string => Str::upper(Str::substr($name, 0, 1)))
            ->implode('');

        return view('machines.index', [
            'activeMenu' => 'data-mesin',
            'pageTitle' => 'Data Mesin',
            'pageDescription' => 'Kelola data mesin/peralatan X-Ray yang digunakan untuk preventive maintenance.',
            'userName' => $user->name,
            'userRole' => $user->role->label(),
            'userInitials' => $initials,
        ]);
    })->name('machines.index');

    Route::get('/checklists', function (Request $request): View {
        $user = $request->user();
        $initials = Str::of($user->name)
            ->trim()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $name): string => Str::upper(Str::substr($name, 0, 1)))
            ->implode('');

        return view('checklists.index', [
            'activeMenu' => 'master-checklist',
            'pageTitle' => 'Master Checklist',
            'pageDescription' => 'Kelola kategori dan item pemeriksaan preventive maintenance mesin X-Ray.',
            'userName' => $user->name,
            'userRole' => $user->role->label(),
            'userInitials' => $initials,
        ]);
    })->name('checklists.index');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
