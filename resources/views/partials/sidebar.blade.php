<aside class="sidebar" aria-label="Navigasi utama">
    <div class="sidebar__header">
        <a class="brand" href="{{ route('dashboard') }}" aria-label="SIPM X-Ray">
            <span class="brand__mark" aria-hidden="true">
                <i data-lucide="scan-line"></i>
            </span>
            <span class="brand__text">
                <strong>SIPM X-Ray</strong>
                <small>Preventive Maintenance</small>
            </span>
        </a>
    </div>

    <nav class="sidebar__navigation">
        <section class="sidebar-menu" aria-labelledby="menu-utama">
            <h2 class="sidebar-menu__label" id="menu-utama">Utama</h2>
            <div class="sidebar-menu__items">
                <a href="{{ route('dashboard') }}" @class(['sidebar-menu__link', 'is-active' => $activeMenu === 'dashboard']) @if ($activeMenu === 'dashboard') aria-current="page" @endif>
                    <i data-lucide="layout-dashboard" aria-hidden="true"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#" @class(['sidebar-menu__link', 'is-active' => $activeMenu === 'pemeriksaan-hari-ini']) @if ($activeMenu === 'pemeriksaan-hari-ini') aria-current="page" @endif>
                    <i data-lucide="clipboard-check" aria-hidden="true"></i>
                    <span>Pemeriksaan Hari Ini</span>
                    <span class="sidebar-menu__count" aria-label="4 pemeriksaan">4</span>
                </a>
            </div>
        </section>

        <section class="sidebar-menu" aria-labelledby="menu-master-data">
            <h2 class="sidebar-menu__label" id="menu-master-data">Master Data</h2>
            <div class="sidebar-menu__items">
                <a href="{{ route('machines.index') }}" @class(['sidebar-menu__link', 'is-active' => $activeMenu === 'data-mesin']) @if ($activeMenu === 'data-mesin') aria-current="page" @endif>
                    <i data-lucide="scan-line" aria-hidden="true"></i>
                    <span>Data Mesin</span>
                </a>
                <a href="{{ route('checklists.index') }}" @class(['sidebar-menu__link', 'is-active' => $activeMenu === 'master-checklist']) @if ($activeMenu === 'master-checklist') aria-current="page" @endif>
                    <i data-lucide="clipboard-list" aria-hidden="true"></i>
                    <span>Master Checklist</span>
                </a>
                <a href="#" @class(['sidebar-menu__link', 'is-active' => $activeMenu === 'jadwal-maintenance']) @if ($activeMenu === 'jadwal-maintenance') aria-current="page" @endif>
                    <i data-lucide="calendar-days" aria-hidden="true"></i>
                    <span>Jadwal Maintenance</span>
                </a>
                <a href="#" @class(['sidebar-menu__link', 'is-active' => $activeMenu === 'user-management']) @if ($activeMenu === 'user-management') aria-current="page" @endif>
                    <i data-lucide="users" aria-hidden="true"></i>
                    <span>User Management</span>
                </a>
            </div>
        </section>

        <section class="sidebar-menu" aria-labelledby="menu-monitoring">
            <h2 class="sidebar-menu__label" id="menu-monitoring">Monitoring</h2>
            <div class="sidebar-menu__items">
                <a href="#" @class(['sidebar-menu__link', 'is-active' => $activeMenu === 'temuan']) @if ($activeMenu === 'temuan') aria-current="page" @endif>
                    <i data-lucide="triangle-alert" aria-hidden="true"></i>
                    <span>Temuan</span>
                    <span class="sidebar-menu__count sidebar-menu__count--danger" aria-label="3 temuan">3</span>
                </a>
                <a href="#" @class(['sidebar-menu__link', 'is-active' => $activeMenu === 'riwayat-pemeriksaan']) @if ($activeMenu === 'riwayat-pemeriksaan') aria-current="page" @endif>
                    <i data-lucide="history" aria-hidden="true"></i>
                    <span>Riwayat Pemeriksaan</span>
                </a>
                <a href="#" @class(['sidebar-menu__link', 'is-active' => $activeMenu === 'laporan']) @if ($activeMenu === 'laporan') aria-current="page" @endif>
                    <i data-lucide="file-chart-column" aria-hidden="true"></i>
                    <span>Laporan</span>
                </a>
            </div>
        </section>
    </nav>

    <div class="sidebar__footer">
        <section class="daily-progress" aria-labelledby="daily-progress-title">
            <div class="daily-progress__header">
                <div>
                    <p id="daily-progress-title">Progress hari ini</p>
                    <span>20 dari 24 checklist selesai</span>
                </div>
                <strong>83%</strong>
            </div>
            <div class="daily-progress__track" role="progressbar" aria-label="Progress checklist hari ini" aria-valuemin="0" aria-valuemax="24" aria-valuenow="20">
                <span style="width: 83.33%"></span>
            </div>
        </section>

        <div class="sidebar-user">
            <span class="avatar avatar--sidebar" aria-hidden="true">{{ $userInitials }}</span>
            <span class="sidebar-user__identity">
                <strong>{{ $userName }}</strong>
                <small>{{ $userRole }}</small>
            </span>
            <form class="sidebar-user__logout" method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="icon-button icon-button--dark" type="submit" aria-label="Keluar dari sistem" title="Logout">
                    <i data-lucide="log-out" aria-hidden="true"></i>
                </button>
            </form>
        </div>
    </div>
</aside>
