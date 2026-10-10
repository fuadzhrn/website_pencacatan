@php
    $dummyUsers = [
        [
            'id' => 'user-1',
            'name' => 'Ahmad Admin',
            'email' => 'admin@example.com',
            'phone' => '0821xxxx',
            'role' => 'admin',
            'role_label' => 'Admin',
            'status' => 'active',
            'status_label' => 'Aktif',
            'created_at' => '15 Januari 2026',
            'last_activity' => 'Hari ini, 08.42 WITA',
        ],
        [
            'id' => 'user-2',
            'name' => 'Budi Santoso',
            'email' => 'petugas@example.com',
            'phone' => '0822xxxx',
            'role' => 'petugas',
            'role_label' => 'Petugas',
            'status' => 'active',
            'status_label' => 'Aktif',
            'created_at' => '20 Januari 2026',
            'last_activity' => 'Hari ini, 08.15 WITA',
        ],
        [
            'id' => 'user-3',
            'name' => 'Siti Rahma',
            'email' => 'supervisor@example.com',
            'phone' => '0823xxxx',
            'role' => 'supervisor',
            'role_label' => 'Supervisor',
            'status' => 'active',
            'status_label' => 'Aktif',
            'created_at' => '22 Januari 2026',
            'last_activity' => 'Kemarin, 16.30 WITA',
        ],
        [
            'id' => 'user-4',
            'name' => 'Rudi Hartono',
            'email' => 'rudi@example.com',
            'phone' => '0824xxxx',
            'role' => 'petugas',
            'role_label' => 'Petugas',
            'status' => 'inactive',
            'status_label' => 'Nonaktif',
            'created_at' => '10 Februari 2026',
            'last_activity' => '03 Oktober 2026, 14.12 WITA',
        ],
    ];
@endphp

<div class="mobile-users" data-mobile-users>
    <header class="mobile-users-header">
        <span class="mobile-users-header__icon" aria-hidden="true"><i data-lucide="users-round"></i></span>
        <div>
            <p>Administrasi akses</p>
            <h1>User Management</h1>
            <span>Kelola akun pengguna sistem</span>
        </div>
    </header>

    <section class="mobile-users-panel mobile-users-filter" aria-labelledby="mobile-users-filter-title">
        <div class="mobile-users-section-heading">
            <div><p>Pencarian data</p><h2 id="mobile-users-filter-title">Filter User</h2></div>
            <i data-lucide="list-filter" aria-hidden="true"></i>
        </div>
        <form data-mobile-users-filter>
            <label class="mobile-users-field mobile-users-field--wide">
                <span>Nama atau email</span>
                <span class="mobile-users-input"><i data-lucide="search" aria-hidden="true"></i><input type="search" name="query" placeholder="Cari nama atau email" autocomplete="off"></span>
            </label>
            <label class="mobile-users-field">
                <span>Role</span>
                <select name="role">
                    <option value="">Semua role</option>
                    <option value="admin">Admin</option>
                    <option value="petugas">Petugas</option>
                    <option value="supervisor">Supervisor</option>
                </select>
            </label>
            <label class="mobile-users-field">
                <span>Status</span>
                <select name="status">
                    <option value="">Semua status</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Nonaktif</option>
                </select>
            </label>
            <div class="mobile-users-filter__actions">
                <button class="mobile-users-button mobile-users-button--primary" type="submit"><i data-lucide="filter" aria-hidden="true"></i>Terapkan</button>
                <button class="mobile-users-button mobile-users-button--secondary" type="button" data-mobile-users-reset><i data-lucide="rotate-ccw" aria-hidden="true"></i>Reset</button>
            </div>
        </form>
    </section>

    <section aria-labelledby="mobile-users-summary-title">
        <div class="mobile-users-section-heading mobile-users-section-heading--outside">
            <div><p>Ringkasan akun</p><h2 id="mobile-users-summary-title">Statistik User</h2></div>
            <span data-mobile-users-result-count role="status" aria-live="polite">4 user</span>
        </div>
        <div class="mobile-users-summary">
            <article class="mobile-users-summary-card mobile-users-summary-card--total"><i data-lucide="users" aria-hidden="true"></i><div><small>Total User</small><strong data-mobile-users-summary="total">4</strong></div></article>
            <article class="mobile-users-summary-card mobile-users-summary-card--admin"><i data-lucide="shield-check" aria-hidden="true"></i><div><small>Admin</small><strong data-mobile-users-summary="admin">1</strong></div></article>
            <article class="mobile-users-summary-card mobile-users-summary-card--officer"><i data-lucide="wrench" aria-hidden="true"></i><div><small>Petugas</small><strong data-mobile-users-summary="petugas">2</strong></div></article>
            <article class="mobile-users-summary-card mobile-users-summary-card--supervisor"><i data-lucide="user-check" aria-hidden="true"></i><div><small>Supervisor</small><strong data-mobile-users-summary="supervisor">1</strong></div></article>
        </div>
    </section>

    <section class="mobile-users-directory" aria-labelledby="mobile-users-list-title">
        <div class="mobile-users-section-heading mobile-users-section-heading--outside">
            <div><p>Daftar akses sistem</p><h2 id="mobile-users-list-title">Data User</h2></div>
            <i data-lucide="contact-round" aria-hidden="true"></i>
        </div>
        <div class="mobile-users-list" data-mobile-users-list>
            @foreach ($dummyUsers as $dummyUser)
                <article
                    class="mobile-user-card"
                    data-mobile-users-card
                    data-id="{{ $dummyUser['id'] }}"
                    data-name="{{ $dummyUser['name'] }}"
                    data-email="{{ $dummyUser['email'] }}"
                    data-phone="{{ $dummyUser['phone'] }}"
                    data-role="{{ $dummyUser['role'] }}"
                    data-role-label="{{ $dummyUser['role_label'] }}"
                    data-status="{{ $dummyUser['status'] }}"
                    data-status-label="{{ $dummyUser['status_label'] }}"
                    data-created-at="{{ $dummyUser['created_at'] }}"
                    data-last-activity="{{ $dummyUser['last_activity'] }}"
                >
                    <header>
                        <span class="mobile-user-card__avatar" aria-hidden="true">{{ collect(explode(' ', $dummyUser['name']))->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') }}</span>
                        <div><h3>{{ $dummyUser['name'] }}</h3><a href="mailto:{{ $dummyUser['email'] }}">{{ $dummyUser['email'] }}</a></div>
                        <button type="button" data-mobile-users-actions-open data-user-id="{{ $dummyUser['id'] }}" aria-label="Buka aksi untuk {{ $dummyUser['name'] }}"><i data-lucide="ellipsis-vertical" aria-hidden="true"></i></button>
                    </header>
                    <div class="mobile-user-card__meta">
                        <span class="mobile-users-badge mobile-users-badge--role-{{ $dummyUser['role'] }}">{{ $dummyUser['role_label'] }}</span>
                        <span class="mobile-users-badge mobile-users-badge--status-{{ $dummyUser['status'] }}">{{ $dummyUser['status_label'] }}</span>
                        <span><i data-lucide="phone" aria-hidden="true"></i>{{ $dummyUser['phone'] }}</span>
                    </div>
                    <footer>
                        <button type="button" data-mobile-users-detail-open data-user-id="{{ $dummyUser['id'] }}"><i data-lucide="eye" aria-hidden="true"></i>Detail</button>
                        <button type="button" data-mobile-users-form-open data-user-id="{{ $dummyUser['id'] }}"><i data-lucide="square-pen" aria-hidden="true"></i>Edit</button>
                    </footer>
                </article>
            @endforeach
        </div>
        <div class="mobile-users-empty" data-mobile-users-empty hidden>
            <i data-lucide="user-round-search" aria-hidden="true"></i>
            <h3>User tidak ditemukan</h3>
            <p>Ubah filter atau gunakan kata pencarian lain.</p>
        </div>
    </section>

    <button class="mobile-users-fab" type="button" data-mobile-users-add aria-label="Tambah User"><i data-lucide="plus" aria-hidden="true"></i><span>Tambah User</span></button>

    <template data-mobile-users-card-template>
        <article class="mobile-user-card" data-mobile-users-card>
            <header>
                <span class="mobile-user-card__avatar" data-card-field="initials" aria-hidden="true"></span>
                <div><h3 data-card-field="name"></h3><a data-card-field="email-link"><span data-card-field="email"></span></a></div>
                <button type="button" data-mobile-users-actions-open aria-label="Buka aksi user"><i data-lucide="ellipsis-vertical" aria-hidden="true"></i></button>
            </header>
            <div class="mobile-user-card__meta">
                <span class="mobile-users-badge" data-card-field="role"></span>
                <span class="mobile-users-badge" data-card-field="status"></span>
                <span><i data-lucide="phone" aria-hidden="true"></i><span data-card-field="phone"></span></span>
            </div>
            <footer>
                <button type="button" data-mobile-users-detail-open><i data-lucide="eye" aria-hidden="true"></i>Detail</button>
                <button type="button" data-mobile-users-form-open><i data-lucide="square-pen" aria-hidden="true"></i>Edit</button>
            </footer>
        </article>
    </template>

    <section class="mobile-users-sheet" data-mobile-users-sheet="detail" aria-hidden="true" hidden>
        <button class="mobile-users-sheet__overlay" type="button" data-mobile-users-sheet-close tabindex="-1" aria-label="Tutup detail user"></button>
        <article class="mobile-users-sheet__panel" role="dialog" aria-modal="true" aria-labelledby="mobile-users-detail-title" tabindex="-1">
            <div class="mobile-users-sheet__handle" aria-hidden="true"></div>
            <header class="mobile-users-sheet__header">
                <div><p>Informasi akun</p><h2 id="mobile-users-detail-title">Detail User</h2></div>
                <button type="button" data-mobile-users-sheet-close aria-label="Tutup detail"><i data-lucide="x" aria-hidden="true"></i></button>
            </header>
            <div class="mobile-users-detail-identity">
                <span data-mobile-users-detail="initials" aria-hidden="true">-</span>
                <div><h3 data-mobile-users-detail="name">-</h3><p data-mobile-users-detail="email">-</p></div>
            </div>
            <dl class="mobile-users-detail-grid">
                <div><dt>Role</dt><dd><span class="mobile-users-badge" data-mobile-users-detail="role">-</span></dd></div>
                <div><dt>Status</dt><dd><span class="mobile-users-badge" data-mobile-users-detail="status">-</span></dd></div>
                <div><dt>Nomor HP</dt><dd data-mobile-users-detail="phone">-</dd></div>
                <div><dt>Tanggal dibuat</dt><dd data-mobile-users-detail="createdAt">-</dd></div>
                <div class="mobile-users-detail-grid__wide"><dt>Aktivitas terakhir</dt><dd data-mobile-users-detail="lastActivity">-</dd></div>
            </dl>
            <footer class="mobile-users-sheet__footer"><button class="mobile-users-button mobile-users-button--secondary" type="button" data-mobile-users-sheet-close>Tutup</button></footer>
        </article>
    </section>

    <section class="mobile-users-sheet" data-mobile-users-sheet="form" aria-hidden="true" hidden>
        <button class="mobile-users-sheet__overlay" type="button" data-mobile-users-sheet-close tabindex="-1" aria-label="Tutup form user"></button>
        <article class="mobile-users-sheet__panel mobile-users-sheet__panel--form" role="dialog" aria-modal="true" aria-labelledby="mobile-users-form-title" tabindex="-1">
            <div class="mobile-users-sheet__handle" aria-hidden="true"></div>
            <header class="mobile-users-sheet__header">
                <div><p>Data akun</p><h2 id="mobile-users-form-title" data-mobile-users-form-title>Tambah User</h2></div>
                <button type="button" data-mobile-users-sheet-close aria-label="Tutup form"><i data-lucide="x" aria-hidden="true"></i></button>
            </header>
            <form class="mobile-users-form" data-mobile-users-form>
                <input type="hidden" name="id">
                <label class="mobile-users-field"><span>Nama lengkap</span><input type="text" name="name" placeholder="Masukkan nama lengkap" required></label>
                <label class="mobile-users-field"><span>Email</span><input type="email" name="email" placeholder="nama@contoh.com" required></label>
                <label class="mobile-users-field"><span>Nomor HP</span><input type="tel" name="phone" placeholder="08xxxxxxxxxx" required></label>
                <div class="mobile-users-form__row">
                    <label class="mobile-users-field"><span>Role</span><select name="role" required><option value="admin">Admin</option><option value="petugas">Petugas</option><option value="supervisor">Supervisor</option></select></label>
                    <label class="mobile-users-field"><span>Status</span><select name="status" required><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select></label>
                </div>
                <label class="mobile-users-field"><span>Password <small data-mobile-users-password-hint>(wajib)</small></span><span class="mobile-users-password"><input type="password" name="password" minlength="8" autocomplete="new-password"><button type="button" data-mobile-users-password-toggle aria-label="Tampilkan password"><i data-lucide="eye" aria-hidden="true"></i></button></span></label>
                <label class="mobile-users-field"><span>Konfirmasi password</span><input type="password" name="passwordConfirmation" minlength="8" autocomplete="new-password"></label>
                <p class="mobile-users-form__error" data-mobile-users-form-error role="alert" hidden></p>
                <footer class="mobile-users-form__actions">
                    <button class="mobile-users-button mobile-users-button--secondary" type="button" data-mobile-users-sheet-close>Batal</button>
                    <button class="mobile-users-button mobile-users-button--primary" type="submit"><i data-lucide="save" aria-hidden="true"></i>Simpan</button>
                </footer>
            </form>
        </article>
    </section>

    <section class="mobile-users-sheet mobile-users-sheet--compact" data-mobile-users-sheet="actions" aria-hidden="true" hidden>
        <button class="mobile-users-sheet__overlay" type="button" data-mobile-users-sheet-close tabindex="-1" aria-label="Tutup menu aksi"></button>
        <article class="mobile-users-sheet__panel" role="dialog" aria-modal="true" aria-labelledby="mobile-users-actions-title" tabindex="-1">
            <div class="mobile-users-sheet__handle" aria-hidden="true"></div>
            <header class="mobile-users-sheet__header">
                <div><p>Kelola akun</p><h2 id="mobile-users-actions-title" data-mobile-users-actions-title>Aksi User</h2></div>
                <button type="button" data-mobile-users-sheet-close aria-label="Tutup menu aksi"><i data-lucide="x" aria-hidden="true"></i></button>
            </header>
            <div class="mobile-users-actions">
                <button type="button" data-mobile-users-toggle-status><i data-lucide="user-round-cog" aria-hidden="true"></i><span><strong data-mobile-users-toggle-label>Ubah Status</strong><small>Aktifkan atau nonaktifkan akun</small></span><i data-lucide="chevron-right" aria-hidden="true"></i></button>
                <button type="button" data-mobile-users-reset-password><i data-lucide="key-round" aria-hidden="true"></i><span><strong>Reset Password</strong><small>Kirim instruksi reset password dummy</small></span><i data-lucide="chevron-right" aria-hidden="true"></i></button>
                <button class="mobile-users-actions__danger" type="button" data-mobile-users-delete><i data-lucide="trash-2" aria-hidden="true"></i><span><strong>Hapus User</strong><small>Hapus akun dari daftar simulasi</small></span><i data-lucide="chevron-right" aria-hidden="true"></i></button>
            </div>
        </article>
    </section>

    <div class="mobile-users-toast" role="status" aria-live="polite" data-mobile-users-toast hidden>
        <i data-lucide="circle-check" aria-hidden="true"></i>
        <p data-mobile-users-toast-message>Aksi berhasil dilakukan.</p>
        <button type="button" data-mobile-users-toast-close aria-label="Tutup notifikasi"><i data-lucide="x" aria-hidden="true"></i></button>
    </div>
</div>
