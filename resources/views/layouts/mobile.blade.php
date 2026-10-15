<section class="mobile-shell" data-mobile-shell aria-label="Navigasi dan konten mobile">
    <header class="mobile-topbar">
        <button class="mobile-icon-button" type="button" data-mobile-drawer-open aria-controls="mobile-drawer" aria-expanded="false" aria-label="Buka menu navigasi">
            <i data-lucide="menu" aria-hidden="true"></i>
        </button>

        <a class="mobile-topbar__brand" href="{{ route('mobile.dashboard') }}" aria-label="PM X-Ray - Dashboard mobile">
            <span aria-hidden="true"><i data-lucide="scan-line"></i></span>
            <strong>PM X-Ray</strong>
        </a>

        <div class="mobile-topbar__actions">
            <button class="mobile-icon-button mobile-notification" type="button" data-mobile-notification aria-label="Notifikasi, 2 belum dibaca">
                <i data-lucide="bell" aria-hidden="true"></i>
                <span aria-hidden="true">2</span>
            </button>
            <span class="mobile-avatar" aria-label="Pengguna {{ $userName }}">{{ $userInitials }}</span>
        </div>
    </header>

    <button class="mobile-drawer-overlay" type="button" data-mobile-drawer-close tabindex="-1" aria-hidden="true" aria-label="Tutup menu navigasi"></button>

    <aside class="mobile-drawer" id="mobile-drawer" data-mobile-drawer aria-hidden="true" aria-label="Menu lengkap mobile" inert>
        <header class="mobile-drawer__header">
            <a class="mobile-drawer__brand" href="{{ route('mobile.dashboard') }}">
                <span aria-hidden="true"><i data-lucide="scan-line"></i></span>
                <span><strong>PM X-Ray</strong><small>Preventive Maintenance</small></span>
            </a>
            <button class="mobile-icon-button mobile-drawer__close" type="button" data-mobile-drawer-close aria-label="Tutup menu navigasi">
                <i data-lucide="x" aria-hidden="true"></i>
            </button>
        </header>

        <div class="mobile-drawer__user">
            <span class="mobile-avatar mobile-avatar--large" aria-hidden="true">{{ $userInitials }}</span>
            <div><strong>{{ $userName }}</strong><small>{{ $userRole }} Sistem</small></div>
            <span class="mobile-badge mobile-badge--verified">Aktif</span>
        </div>

        <nav class="mobile-drawer__navigation" aria-label="Menu mobile">
            <p>Menu Utama</p>
            <a href="{{ route('mobile.dashboard') }}" @class(['mobile-drawer__link', 'is-active' => $activeMenu === 'dashboard']) data-mobile-nav-link data-mobile-route="dashboard" @if ($activeMenu === 'dashboard') aria-current="page" @endif>
                <i data-lucide="layout-dashboard" aria-hidden="true"></i><span>Dashboard</span><i data-lucide="chevron-right" aria-hidden="true"></i>
            </a>
            @if ($userRole === 'Admin')
                <a href="{{ route('mobile.machines') }}" @class(['mobile-drawer__link', 'is-active' => $activeMenu === 'data-mesin']) data-mobile-nav-link data-mobile-route="machines" @if ($activeMenu === 'data-mesin') aria-current="page" @endif>
                    <i data-lucide="scan-line" aria-hidden="true"></i><span>Data Mesin</span><i data-lucide="chevron-right" aria-hidden="true"></i>
                </a>
                <a href="{{ route('mobile.checklists') }}" @class(['mobile-drawer__link', 'is-active' => $activeMenu === 'master-checklist']) data-mobile-nav-link data-mobile-route="checklists" @if ($activeMenu === 'master-checklist') aria-current="page" @endif>
                    <i data-lucide="list-checks" aria-hidden="true"></i><span>Master Checklist</span><i data-lucide="chevron-right" aria-hidden="true"></i>
                </a>
            @endif
            <a href="{{ route('mobile.schedules') }}" @class(['mobile-drawer__link', 'is-active' => $activeMenu === 'jadwal-maintenance']) data-mobile-nav-link data-mobile-route="schedules" @if ($activeMenu === 'jadwal-maintenance') aria-current="page" @endif>
                <i data-lucide="calendar-clock" aria-hidden="true"></i><span>Jadwal Maintenance</span><i data-lucide="chevron-right" aria-hidden="true"></i>
            </a>
            <a href="{{ route('mobile.inspection-today') }}" @class(['mobile-drawer__link', 'is-active' => $activeMenu === 'pemeriksaan-hari-ini']) data-mobile-nav-link data-mobile-route="inspection-today" @if ($activeMenu === 'pemeriksaan-hari-ini') aria-current="page" @endif>
                <i data-lucide="clipboard-check" aria-hidden="true"></i><span>Pemeriksaan Hari Ini</span><i data-lucide="chevron-right" aria-hidden="true"></i>
            </a>
            <a href="{{ route('mobile.findings') }}" @class(['mobile-drawer__link', 'is-active' => $activeMenu === 'temuan']) data-mobile-nav-link data-mobile-route="findings" @if ($activeMenu === 'temuan') aria-current="page" @endif>
                <i data-lucide="triangle-alert" aria-hidden="true"></i><span>Temuan</span><i data-lucide="chevron-right" aria-hidden="true"></i>
            </a>
            @if ($userRole !== 'Petugas')
                <a href="{{ route('verifications.index') }}" @class(['mobile-drawer__link', 'is-active' => $activeMenu === 'verifikasi-pemeriksaan']) data-mobile-nav-link data-mobile-route="verifications" @if ($activeMenu === 'verifikasi-pemeriksaan') aria-current="page" @endif>
                    <i data-lucide="badge-check" aria-hidden="true"></i><span>Verifikasi Pemeriksaan</span><i data-lucide="chevron-right" aria-hidden="true"></i>
                </a>
            @endif
            <a href="{{ route('mobile.history') }}" @class(['mobile-drawer__link', 'is-active' => $activeMenu === 'riwayat-pemeriksaan']) data-mobile-nav-link data-mobile-route="history" @if ($activeMenu === 'riwayat-pemeriksaan') aria-current="page" @endif>
                <i data-lucide="history" aria-hidden="true"></i><span>Riwayat Pemeriksaan</span><i data-lucide="chevron-right" aria-hidden="true"></i>
            </a>
            <a href="{{ route('mobile.report') }}" @class(['mobile-drawer__link', 'is-active' => $activeMenu === 'laporan']) data-mobile-nav-link data-mobile-route="report" @if ($activeMenu === 'laporan') aria-current="page" @endif>
                <i data-lucide="file-chart-column" aria-hidden="true"></i><span>Laporan</span><i data-lucide="chevron-right" aria-hidden="true"></i>
            </a>
            @if ($userRole === 'Admin')
                <a href="{{ route('mobile.users') }}" @class(['mobile-drawer__link', 'is-active' => $activeMenu === 'user-management']) data-mobile-nav-link data-mobile-route="users" data-user-management-navigation="mobile" @if ($activeMenu === 'user-management') aria-current="page" @endif>
                    <i data-lucide="users-round" aria-hidden="true"></i><span>User Management</span><i data-lucide="chevron-right" aria-hidden="true"></i>
                </a>
            @endif
        </nav>

        <footer class="mobile-drawer__footer">
            <div><i data-lucide="shield-check" aria-hidden="true"></i><span><strong>Sistem internal</strong><small>UPBU Malikussaleh</small></span></div>
            <button type="button" data-mobile-logout><i data-lucide="log-out" aria-hidden="true"></i>Logout</button>
        </footer>
    </aside>

    <nav class="mobile-bottom-navigation" data-mobile-bottom-nav aria-label="Menu utama mobile">
        <a href="{{ route('mobile.dashboard') }}" @class(['mobile-bottom-navigation__item', 'is-active' => $activeMenu === 'dashboard']) data-mobile-nav-link data-mobile-route="dashboard" @if ($activeMenu === 'dashboard') aria-current="page" @endif>
            <i data-lucide="layout-dashboard" aria-hidden="true"></i><span>Dashboard</span>
        </a>
        <a href="{{ route('mobile.schedules') }}" @class(['mobile-bottom-navigation__item', 'is-active' => $activeMenu === 'jadwal-maintenance']) data-mobile-nav-link data-mobile-route="schedules" @if ($activeMenu === 'jadwal-maintenance') aria-current="page" @endif>
            <i data-lucide="calendar-clock" aria-hidden="true"></i><span>Jadwal</span>
        </a>
        <a href="{{ route('mobile.inspection-today') }}" @class(['mobile-bottom-navigation__item', 'mobile-bottom-navigation__item--primary', 'is-active' => $activeMenu === 'pemeriksaan-hari-ini']) data-mobile-nav-link data-mobile-route="inspection-today" @if ($activeMenu === 'pemeriksaan-hari-ini') aria-current="page" @endif>
            <span class="mobile-bottom-navigation__primary-icon"><i data-lucide="clipboard-check" aria-hidden="true"></i></span><span>Periksa</span>
        </a>
        <a href="{{ route('mobile.findings') }}" @class(['mobile-bottom-navigation__item', 'is-active' => $activeMenu === 'temuan']) data-mobile-nav-link data-mobile-route="findings" @if ($activeMenu === 'temuan') aria-current="page" @endif>
            <i data-lucide="triangle-alert" aria-hidden="true"></i><span>Temuan</span><small aria-label="{{ $findingActiveCount ?? 4 }} temuan aktif">{{ $findingActiveCount ?? 4 }}</small>
        </a>
        <a href="{{ route('mobile.report') }}" @class(['mobile-bottom-navigation__item', 'is-active' => $activeMenu === 'laporan']) data-mobile-nav-link data-mobile-route="report" @if ($activeMenu === 'laporan') aria-current="page" @endif>
            <i data-lucide="file-chart-column" aria-hidden="true"></i><span>Laporan</span>
        </a>
    </nav>

    <div class="mobile-navigation-toast" data-mobile-navigation-toast role="status" aria-live="polite" hidden>
        <i data-lucide="info" aria-hidden="true"></i>
        <span data-mobile-navigation-toast-message>Fitur ini masih berupa simulasi.</span>
        <button type="button" data-mobile-navigation-toast-close aria-label="Tutup notifikasi"><i data-lucide="x" aria-hidden="true"></i></button>
    </div>

    @if ($activeMenu === 'dashboard')
        <link rel="stylesheet" href="{{ asset('assets/css/mobile-dashboard.css') }}" media="screen and (max-width: 768px)">
        @include('mobile.dashboard', [
            'userName' => $userName,
            'userRole' => $userRole,
        ])
        <script type="module" src="{{ asset('assets/js/mobile-dashboard.js') }}"></script>
    @elseif ($activeMenu === 'pemeriksaan-hari-ini')
        <link rel="stylesheet" href="{{ asset('assets/css/mobile-inspection.css') }}" media="screen and (max-width: 768px)">
        @include('mobile.inspection-today', [
            'userName' => $userName,
            'userRole' => $userRole,
        ])
        <script type="module" src="{{ asset('assets/js/mobile-inspection.js') }}"></script>
    @elseif ($activeMenu === 'master-checklist')
        <link rel="stylesheet" href="{{ asset('assets/css/mobile-checklists.css') }}" media="screen and (max-width: 768px)">
        @include('mobile.checklists')
        <script type="module" src="{{ asset('assets/js/mobile-checklists.js') }}"></script>
    @elseif ($activeMenu === 'data-mesin')
        @include('mobile.machines')
    @elseif ($activeMenu === 'jadwal-maintenance')
        <link rel="stylesheet" href="{{ asset('assets/css/mobile-schedules.css') }}" media="screen and (max-width: 768px)">
        @include('mobile.schedules')
        <script type="module" src="{{ asset('assets/js/mobile-schedules.js') }}"></script>
    @elseif ($activeMenu === 'temuan')
        <link rel="stylesheet" href="{{ asset('assets/css/mobile-findings.css') }}" media="screen and (max-width: 768px)">
        @include('mobile.findings-data-partial')
        <script src="{{ asset('assets/js/findings-database.js') }}" defer></script>
    @elseif (in_array($activeMenu, ['riwayat-pemeriksaan', 'verifikasi-pemeriksaan'], true))
        <link rel="stylesheet" href="{{ asset('assets/css/verifications.css') }}" media="screen and (max-width: 768px)">
        @include('mobile.history-database')
    @elseif ($activeMenu === 'user-management')
        <link rel="stylesheet" href="{{ asset('assets/css/mobile-users.css') }}" media="screen and (max-width: 768px)">
        @include('mobile.users')
        <script type="module" src="{{ asset('assets/js/mobile-users.js') }}"></script>
    @elseif ($activeMenu === 'laporan')
        <link rel="stylesheet" href="{{ asset('assets/css/mobile-report.css') }}" media="screen and (max-width: 768px)">
        @include('mobile.report-database')
    @endif
</section>
