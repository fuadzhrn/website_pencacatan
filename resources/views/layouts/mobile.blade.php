<section class="mobile-shell" data-mobile-shell aria-label="Navigasi dan konten mobile">
    <header class="mobile-topbar">
        <button class="mobile-icon-button" type="button" data-mobile-drawer-open aria-controls="mobile-drawer" aria-expanded="false" aria-label="Buka menu navigasi">
            <i data-lucide="menu" aria-hidden="true"></i>
        </button>

        <a class="mobile-topbar__brand" href="{{ route('dashboard') }}" aria-label="PM X-Ray - Dashboard">
            <span aria-hidden="true"><i data-lucide="scan-line"></i></span>
            <strong>PM X-Ray</strong>
        </a>

        <div class="mobile-topbar__actions">
            <button class="mobile-icon-button mobile-notification" type="button" aria-label="Notifikasi, 2 belum dibaca">
                <i data-lucide="bell" aria-hidden="true"></i>
                <span aria-hidden="true">2</span>
            </button>
            <span class="mobile-avatar" aria-label="Pengguna {{ $userName }}">{{ $userInitials }}</span>
        </div>
    </header>

    <button class="mobile-drawer-overlay" type="button" data-mobile-drawer-close tabindex="-1" aria-hidden="true" aria-label="Tutup menu navigasi"></button>

    <aside class="mobile-drawer" id="mobile-drawer" data-mobile-drawer aria-hidden="true" aria-label="Menu lengkap mobile" inert>
        <header class="mobile-drawer__header">
            <a class="mobile-drawer__brand" href="{{ route('dashboard') }}">
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

        <nav class="mobile-drawer__navigation" aria-label="Menu tambahan">
            <p>Menu Tambahan</p>
            <a href="{{ route('machines.index') }}" @class(['mobile-drawer__link', 'is-active' => $activeMenu === 'data-mesin']) data-mobile-nav-link>
                <i data-lucide="scan-line" aria-hidden="true"></i><span>Data Mesin</span><i data-lucide="chevron-right" aria-hidden="true"></i>
            </a>
            <a href="{{ route('checklists.index') }}" @class(['mobile-drawer__link', 'is-active' => $activeMenu === 'master-checklist']) data-mobile-nav-link>
                <i data-lucide="list-checks" aria-hidden="true"></i><span>Master Checklist</span><i data-lucide="chevron-right" aria-hidden="true"></i>
            </a>
            <a href="#" @class(['mobile-drawer__link', 'is-active' => $activeMenu === 'riwayat-pemeriksaan']) data-mobile-nav-link data-mobile-dummy-link>
                <i data-lucide="history" aria-hidden="true"></i><span>Riwayat Pemeriksaan</span><i data-lucide="chevron-right" aria-hidden="true"></i>
            </a>
            <a href="#" @class(['mobile-drawer__link', 'is-active' => $activeMenu === 'user-management']) data-mobile-nav-link data-mobile-dummy-link>
                <i data-lucide="users-round" aria-hidden="true"></i><span>User Management</span><i data-lucide="chevron-right" aria-hidden="true"></i>
            </a>
        </nav>

        <footer class="mobile-drawer__footer">
            <div><i data-lucide="shield-check" aria-hidden="true"></i><span><strong>Sistem internal</strong><small>UPBU Malikussaleh</small></span></div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"><i data-lucide="log-out" aria-hidden="true"></i>Logout</button>
            </form>
        </footer>
    </aside>

    <nav class="mobile-bottom-navigation" data-mobile-bottom-nav aria-label="Menu utama mobile">
        <a href="{{ route('dashboard') }}" @class(['mobile-bottom-navigation__item', 'is-active' => $activeMenu === 'dashboard']) data-mobile-nav-link @if ($activeMenu === 'dashboard') aria-current="page" @endif>
            <i data-lucide="layout-dashboard" aria-hidden="true"></i><span>Dashboard</span>
        </a>
        <a href="{{ route('schedules.index') }}" @class(['mobile-bottom-navigation__item', 'is-active' => $activeMenu === 'jadwal-maintenance']) data-mobile-nav-link @if ($activeMenu === 'jadwal-maintenance') aria-current="page" @endif>
            <i data-lucide="calendar-clock" aria-hidden="true"></i><span>Jadwal</span>
        </a>
        <a href="{{ route('inspections.today') }}" @class(['mobile-bottom-navigation__item', 'mobile-bottom-navigation__item--primary', 'is-active' => $activeMenu === 'pemeriksaan-hari-ini']) data-mobile-nav-link @if ($activeMenu === 'pemeriksaan-hari-ini') aria-current="page" @endif>
            <span class="mobile-bottom-navigation__primary-icon"><i data-lucide="clipboard-check" aria-hidden="true"></i></span><span>Periksa</span>
        </a>
        <a href="{{ route('findings.index') }}" @class(['mobile-bottom-navigation__item', 'is-active' => $activeMenu === 'temuan']) data-mobile-nav-link @if ($activeMenu === 'temuan') aria-current="page" @endif>
            <i data-lucide="triangle-alert" aria-hidden="true"></i><span>Temuan</span><small aria-label="4 temuan aktif">4</small>
        </a>
        <a href="{{ route('reports.monthly') }}" @class(['mobile-bottom-navigation__item', 'is-active' => $activeMenu === 'laporan']) data-mobile-nav-link @if ($activeMenu === 'laporan') aria-current="page" @endif>
            <i data-lucide="file-chart-column" aria-hidden="true"></i><span>Laporan</span>
        </a>
    </nav>

    @if ($activeMenu === 'dashboard')
        <div class="mobile-dashboard-preview" data-mobile-dashboard-preview>
            <header class="mobile-page-heading">
                <div><p>Operasional Maintenance</p><h1>Dashboard</h1><span>Kamis, 08 Oktober 2026</span></div>
                <span class="mobile-page-heading__status"><i data-lucide="wifi" aria-hidden="true"></i>Online</span>
            </header>

            <section class="mobile-card mobile-summary-card" aria-labelledby="mobile-summary-title">
                <div class="mobile-card__heading">
                    <span class="mobile-card__icon"><i data-lucide="clipboard-list" aria-hidden="true"></i></span>
                    <div><p>Ringkasan operasional</p><h2 id="mobile-summary-title">Pemeriksaan Hari Ini</h2></div>
                    <strong>12</strong>
                </div>
                <div class="mobile-summary-card__items">
                    <div><span class="mobile-summary-card__marker mobile-summary-card__marker--success"></span><p>Selesai</p><strong>8</strong></div>
                    <div><span class="mobile-summary-card__marker mobile-summary-card__marker--warning"></span><p>Belum Dikerjakan</p><strong>3</strong></div>
                    <div><span class="mobile-summary-card__marker mobile-summary-card__marker--danger"></span><p>Temuan</p><strong>1</strong></div>
                </div>
                <div class="mobile-progress"><span><strong>67%</strong> selesai</span><div><i class="mobile-progress__value--67"></i></div></div>
            </section>

            <section class="mobile-card" aria-labelledby="mobile-schedule-title">
                <div class="mobile-card__heading">
                    <span class="mobile-card__icon mobile-card__icon--charcoal"><i data-lucide="calendar-check-2" aria-hidden="true"></i></span>
                    <div><p>Jadwal terdekat</p><h2 id="mobile-schedule-title">Pemeriksaan Hari Ini</h2></div>
                    <span class="mobile-badge mobile-badge--scheduled">Terjadwal</span>
                </div>
                <div class="mobile-card__body">
                    <h3>Mesin X-Ray Baggage - SCP</h3>
                    <p><i data-lucide="map-pin" aria-hidden="true"></i>Security Check Point Terminal 1</p>
                    <p><i data-lucide="clock-3" aria-hidden="true"></i>08.00 - 10.00 WITA</p>
                </div>
                <a class="mobile-button mobile-button--primary" href="{{ route('inspections.today') }}">Mulai Pemeriksaan<i data-lucide="arrow-right" aria-hidden="true"></i></a>
            </section>

            <section class="mobile-card" aria-labelledby="mobile-finding-title">
                <div class="mobile-card__heading">
                    <span class="mobile-card__icon mobile-card__icon--danger"><i data-lucide="shield-alert" aria-hidden="true"></i></span>
                    <div><p>Perlu tindak lanjut</p><h2 id="mobile-finding-title">Temuan Terbaru</h2></div>
                    <span class="mobile-badge mobile-badge--open">Open</span>
                </div>
                <div class="mobile-card__body">
                    <h3>Conveyor belt tidak stabil</h3>
                    <p>Pergerakan belt tersendat dan terdengar suara gesekan saat unit dijalankan.</p>
                    <small>Hari ini &bull; X-Ray Baggage - SCP</small>
                </div>
                <a class="mobile-button mobile-button--secondary" href="{{ route('findings.index') }}">Lihat Temuan<i data-lucide="chevron-right" aria-hidden="true"></i></a>
            </section>
        </div>
    @endif
</section>
