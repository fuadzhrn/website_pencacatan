<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\UserRole;
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

    Route::get('/schedules', function (Request $request): View {
        $user = $request->user();
        $initials = Str::of($user->name)
            ->trim()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $name): string => Str::upper(Str::substr($name, 0, 1)))
            ->implode('');

        return view('schedules.index', [
            'activeMenu' => 'jadwal-maintenance',
            'pageTitle' => 'Jadwal Maintenance',
            'pageDescription' => 'Pantau jadwal pemeriksaan preventive maintenance mesin X-Ray.',
            'userName' => $user->name,
            'userRole' => $user->role->label(),
            'userInitials' => $initials,
        ]);
    })->name('schedules.index');

    Route::get('/inspections/today', function (Request $request): View {
        $user = $request->user();
        $initials = Str::of($user->name)
            ->trim()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $name): string => Str::upper(Str::substr($name, 0, 1)))
            ->implode('');

        return view('inspections.today', [
            'activeMenu' => 'pemeriksaan-hari-ini',
            'pageTitle' => 'Pemeriksaan Hari Ini',
            'pageDescription' => 'Isi checklist preventive maintenance berdasarkan jadwal hari ini.',
            'userName' => $user->name,
            'userRole' => $user->role->label(),
            'userInitials' => $initials,
        ]);
    })->name('inspections.today');

    Route::get('/inspections/history', function (Request $request): View {
        $user = $request->user();
        $initials = Str::of($user->name)
            ->trim()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $name): string => Str::upper(Str::substr($name, 0, 1)))
            ->implode('');

        return view('inspections.history', [
            'activeMenu' => 'riwayat-pemeriksaan',
            'pageTitle' => 'Riwayat Pemeriksaan',
            'pageDescription' => 'Lihat pemeriksaan preventive maintenance yang sudah dilakukan.',
            'userName' => $user->name,
            'userRole' => $user->role->label(),
            'userInitials' => $initials,
        ]);
    })->name('inspections.history');

    Route::get('/findings', function (Request $request): View {
        $user = $request->user();
        $initials = Str::of($user->name)
            ->trim()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $name): string => Str::upper(Str::substr($name, 0, 1)))
            ->implode('');

        return view('findings.index', [
            'activeMenu' => 'temuan',
            'pageTitle' => 'Temuan Pemeriksaan',
            'pageDescription' => 'Kelola temuan dari hasil preventive maintenance mesin X-Ray.',
            'userName' => $user->name,
            'userRole' => $user->role->label(),
            'userInitials' => $initials,
        ]);
    })->name('findings.index');

    Route::get('/reports/monthly', function (Request $request): View {
        $user = $request->user();
        $initials = Str::of($user->name)
            ->trim()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $name): string => Str::upper(Str::substr($name, 0, 1)))
            ->implode('');

        return view('reports.monthly', [
            'activeMenu' => 'laporan',
            'pageTitle' => 'Laporan Bulanan',
            'pageDescription' => 'Rekap hasil preventive maintenance mesin X-Ray berdasarkan bulan dan tahun.',
            'userName' => $user->name,
            'userRole' => $user->role->label(),
            'userInitials' => $initials,
        ]);
    })->name('reports.monthly');

    Route::get('/users', function (Request $request): View {
        $user = $request->user();

        abort_unless($user->role === UserRole::Admin, 403);

        $initials = Str::of($user->name)
            ->trim()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $name): string => Str::upper(Str::substr($name, 0, 1)))
            ->implode('');

        return view('users.index', [
            'activeMenu' => 'user-management',
            'pageTitle' => 'User Management',
            'pageDescription' => 'Kelola akun pengguna dan hak akses Sistem Preventive Maintenance X-Ray.',
            'userName' => $user->name,
            'userRole' => $user->role->label(),
            'userInitials' => $initials,
        ]);
    })->name('users.index');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
