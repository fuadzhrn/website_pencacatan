<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title', 'Dashboard') | SIPM X-Ray</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&amp;family=Poppins:wght@400;500;600&amp;display=swap" rel="stylesheet">

        <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">

        <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js" defer></script>
        <script type="module" src="{{ asset('assets/js/app.js') }}"></script>
    </head>
    <body class="app-body">
        <a class="skip-link" href="#main-content">Lewati ke konten utama</a>

        <div class="app-shell">
            @include('partials.sidebar', [
                'activeMenu' => $activeMenu ?? 'dashboard',
                'userName' => $userName ?? 'Ahmad Fauzi',
                'userRole' => $userRole ?? 'Admin',
                'userInitials' => $userInitials ?? 'AF',
            ])

            <div class="app-workspace">
                @include('partials.topbar', [
                    'pageTitle' => $pageTitle ?? 'Dashboard',
                    'pageDescription' => $pageDescription ?? 'Pantau aktivitas preventive maintenance hari ini.',
                    'userName' => $userName ?? 'Ahmad Fauzi',
                    'userRole' => $userRole ?? 'Admin',
                    'userInitials' => $userInitials ?? 'AF',
                ])

                <main class="app-main" id="main-content" tabindex="-1">
                    @hasSection('content')
                        @yield('content')
                    @else
                        <section class="layout-placeholder" aria-labelledby="layout-placeholder-title">
                            <div class="layout-placeholder__heading">
                                <div>
                                    <p class="eyebrow">Fondasi antarmuka</p>
                                    <h2 id="layout-placeholder-title">Layout desktop siap digunakan</h2>
                                    <p>
                                        Area ini akan menampilkan konten setiap halaman tanpa mengubah struktur
                                        navigasi utama.
                                    </p>
                                </div>

                                <span class="status-badge status-badge--verified">
                                    <i data-lucide="badge-check" aria-hidden="true"></i>
                                    Layout aktif
                                </span>
                            </div>

                            <div class="layout-placeholder__grid">
                                <article class="foundation-card">
                                    <span class="foundation-card__icon">
                                        <i data-lucide="panel-left" aria-hidden="true"></i>
                                    </span>
                                    <div>
                                        <h3>Sidebar expanded</h3>
                                        <p>Navigasi dibagi menjadi Utama, Master Data, dan Monitoring.</p>
                                    </div>
                                </article>

                                <article class="foundation-card">
                                    <span class="foundation-card__icon">
                                        <i data-lucide="panel-top" aria-hidden="true"></i>
                                    </span>
                                    <div>
                                        <h3>Topbar informatif</h3>
                                        <p>Judul, tanggal, identitas pengguna, dan role selalu mudah ditemukan.</p>
                                    </div>
                                </article>

                                <article class="foundation-card">
                                    <span class="foundation-card__icon">
                                        <i data-lucide="panels-top-left" aria-hidden="true"></i>
                                    </span>
                                    <div>
                                        <h3>Content wrapper</h3>
                                        <p>Panel putih di atas off-white menjaga halaman tetap bersih dan fokus.</p>
                                    </div>
                                </article>
                            </div>
                        </section>
                    @endif
                </main>
            </div>
        </div>
    </body>
</html>
