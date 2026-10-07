<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Halaman login Sistem Informasi Preventive Maintenance X-Ray">

    <title>Login | Sistem Informasi Preventive Maintenance X-Ray</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
</head>
<body class="login-page">
    <main class="login-shell">
        <section class="login-frame" aria-labelledby="system-name">
            <div class="login-branding">
                <div>
                    <div class="login-brand" aria-label="Identitas sistem">
                        <span class="login-brand__mark" aria-hidden="true">
                            <i data-lucide="scan-line"></i>
                        </span>
                        <span class="login-brand__identity">
                            <strong>X-Ray Maintenance</strong>
                            <small>Sistem internal operasional</small>
                        </span>
                    </div>

                    <div class="login-branding__content">
                        <span class="login-eyebrow">UPBU Malikussaleh</span>
                        <h1 id="system-name">Sistem Informasi<br>Preventive Maintenance X-Ray</h1>
                        <p>
                            Monitoring dan pencatatan pemeliharaan mesin X-Ray Baggage
                            dalam satu sistem yang rapi, terukur, dan mudah dipantau.
                        </p>
                    </div>

                    <ul class="login-benefits" aria-label="Keunggulan sistem">
                        <li>
                            <span aria-hidden="true"><i data-lucide="calendar-check-2"></i></span>
                            <div>
                                <strong>Jadwal terpantau</strong>
                                <small>Pemeriksaan berkala tercatat secara terstruktur.</small>
                            </div>
                        </li>
                        <li>
                            <span aria-hidden="true"><i data-lucide="clipboard-check"></i></span>
                            <div>
                                <strong>Checklist digital</strong>
                                <small>Hasil pemeriksaan dan temuan terdokumentasi.</small>
                            </div>
                        </li>
                        <li>
                            <span aria-hidden="true"><i data-lucide="file-chart-column"></i></span>
                            <div>
                                <strong>Laporan terpusat</strong>
                                <small>Data maintenance siap dipantau dan dievaluasi.</small>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="login-branding__footer">
                    <i data-lucide="shield-check" aria-hidden="true"></i>
                    <span>Akses terbatas untuk personel yang berwenang</span>
                </div>
            </div>

            <div class="login-access">
                <div class="login-card">
                    <header class="login-card__header">
                        <span class="login-card__eyebrow">Akses pengguna</span>
                        <h2>Masuk ke Sistem</h2>
                        <p>Gunakan akun internal Anda untuk melanjutkan.</p>
                    </header>

                    @if (session('status'))
                        <div class="alert alert--success login-alert" role="status">
                            <i data-lucide="circle-check" aria-hidden="true"></i>
                            <span>{{ session('status') }}</span>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert--danger login-alert" role="alert">
                            <i data-lucide="circle-alert" aria-hidden="true"></i>
                            <span>Data login belum benar. Silakan periksa kembali.</span>
                        </div>
                    @endif

                    <form class="login-form" method="POST" action="{{ route('login.store') }}">
                        @csrf

                        <div class="login-field">
                            <label for="email">Email</label>
                            <div class="login-field__control">
                                <i data-lucide="mail" aria-hidden="true"></i>
                                <input
                                    id="email"
                                    class="login-input @error('email') is-invalid @enderror"
                                    type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    placeholder="nama@instansi.go.id"
                                    autocomplete="email"
                                    inputmode="email"
                                    required
                                    autofocus
                                    @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                                >
                            </div>
                            @error('email')
                                <p id="email-error" class="form-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="login-field">
                            <label for="password">Password</label>
                            <div class="login-field__control">
                                <i data-lucide="lock-keyhole" aria-hidden="true"></i>
                                <input
                                    id="password"
                                    class="login-input @error('password') is-invalid @enderror"
                                    type="password"
                                    name="password"
                                    placeholder="Masukkan password"
                                    autocomplete="current-password"
                                    required
                                    @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                                >
                            </div>
                            @error('password')
                                <p id="password-error" class="form-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <label class="login-remember" for="remember">
                            <input
                                id="remember"
                                type="checkbox"
                                name="remember"
                                value="1"
                                @checked(old('remember'))
                            >
                            <span>Ingat saya pada perangkat ini</span>
                        </label>

                        <button class="login-submit" type="submit">
                            <span>Login</span>
                            <i data-lucide="arrow-right" aria-hidden="true"></i>
                        </button>
                    </form>

                    <div class="login-card__notice">
                        <i data-lucide="info" aria-hidden="true"></i>
                        <p>Hubungi administrator apabila Anda mengalami kendala akses akun.</p>
                    </div>
                </div>

                <footer class="login-footer">
                    <span>&copy; {{ now()->year }} UPBU Malikussaleh</span>
                    <span aria-hidden="true">&bull;</span>
                    <span>Sistem Internal Maintenance</span>
                </footer>
            </div>
        </section>
    </main>

    <script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script>
    <script type="module" src="{{ asset('assets/js/app.js') }}"></script>
</body>
</html>
