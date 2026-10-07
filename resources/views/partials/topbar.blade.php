<header class="topbar">
    <div class="topbar__heading">
        <p class="eyebrow">Operasional maintenance</p>
        <h1>{{ $pageTitle }}</h1>
        <p>{{ $pageDescription }}</p>
    </div>

    <div class="topbar__actions">
        <div class="topbar-date" aria-label="Tanggal hari ini">
            <span class="topbar-date__icon" aria-hidden="true">
                <i data-lucide="calendar-days"></i>
            </span>
            <span>
                <small>Hari ini</small>
                <time id="current-date">Memuat tanggal...</time>
            </span>
        </div>

        <span class="topbar__divider" aria-hidden="true"></span>

        <div class="topbar-user">
            <span class="avatar" aria-hidden="true">{{ $userInitials }}</span>
            <span class="topbar-user__identity">
                <strong>{{ $userName }}</strong>
                <small>{{ $userRole }}</small>
            </span>
            <button class="icon-button" type="button" aria-label="Buka menu profil">
                <i data-lucide="chevron-down" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</header>
