@php
    $mobileIndexUrl = route('mobile.users');
@endphp

<div class="mobile-users" data-mobile-users data-index-url="{{ $mobileIndexUrl }}" data-has-errors="{{ $errors->any() ? 'true' : 'false' }}" data-editing-user-id="{{ old('editing_user_id') }}">
    <header class="mobile-users-header">
        <span class="mobile-users-header__icon" aria-hidden="true"><i data-lucide="users-round"></i></span>
        <div>
            <p>Administrasi akses</p>
            <h1>User Management</h1>
            <span>Kelola akun pengguna sistem</span>
        </div>
    </header>

    @if (session('success') || session('error') || $errors->any())
        <div class="mobile-users-alert {{ session('error') || $errors->any() ? 'mobile-users-alert--error' : 'mobile-users-alert--success' }}" role="alert">
            <i data-lucide="{{ session('error') || $errors->any() ? 'circle-alert' : 'circle-check' }}" aria-hidden="true"></i>
            <span>{{ session('error') ?? session('success') ?? $errors->first() }}</span>
        </div>
    @endif

    <section class="mobile-users-panel mobile-users-filter" aria-labelledby="mobile-users-filter-title">
        <div class="mobile-users-section-heading">
            <div><p>Pencarian data</p><h2 id="mobile-users-filter-title">Filter User</h2></div>
            <i data-lucide="list-filter" aria-hidden="true"></i>
        </div>
        <form method="GET" action="{{ $mobileIndexUrl }}" data-mobile-users-filter>
            <label class="mobile-users-field mobile-users-field--wide">
                <span>Nama atau email</span>
                <span class="mobile-users-input"><i data-lucide="search" aria-hidden="true"></i><input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Cari nama atau email" autocomplete="off"></span>
            </label>
            <label class="mobile-users-field">
                <span>Role</span>
                <select name="role">
                    <option value="">Semua role</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected($filters['role'] === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
            </label>
            <label class="mobile-users-field">
                <span>Status</span>
                <select name="status">
                    <option value="">Semua status</option>
                    <option value="active" @selected($filters['status'] === 'active')>Aktif</option>
                    <option value="inactive" @selected($filters['status'] === 'inactive')>Nonaktif</option>
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
            <span data-mobile-users-result-count role="status" aria-live="polite">{{ $users->total() }} user</span>
        </div>
        <div class="mobile-users-summary">
            <article class="mobile-users-summary-card mobile-users-summary-card--total"><i data-lucide="users" aria-hidden="true"></i><div><small>Total User</small><strong data-mobile-users-summary="total">{{ $summary['total'] }}</strong></div></article>
            <article class="mobile-users-summary-card mobile-users-summary-card--admin"><i data-lucide="shield-check" aria-hidden="true"></i><div><small>Admin</small><strong data-mobile-users-summary="admin">{{ $summary['admin'] }}</strong></div></article>
            <article class="mobile-users-summary-card mobile-users-summary-card--officer"><i data-lucide="wrench" aria-hidden="true"></i><div><small>Petugas</small><strong data-mobile-users-summary="petugas">{{ $summary['petugas'] }}</strong></div></article>
            <article class="mobile-users-summary-card mobile-users-summary-card--supervisor"><i data-lucide="user-check" aria-hidden="true"></i><div><small>Supervisor</small><strong data-mobile-users-summary="supervisor">{{ $summary['supervisor'] }}</strong></div></article>
        </div>
    </section>

    <section class="mobile-users-directory" aria-labelledby="mobile-users-list-title">
        <div class="mobile-users-section-heading mobile-users-section-heading--outside">
            <div><p>Daftar akses sistem</p><h2 id="mobile-users-list-title">Data User</h2></div>
            <i data-lucide="contact-round" aria-hidden="true"></i>
        </div>
        <div class="mobile-users-list" data-mobile-users-list @if ($users->isEmpty()) hidden @endif>
            @forelse ($users as $managedUser)
                <article
                    class="mobile-user-card"
                    data-mobile-users-card
                    data-id="{{ $managedUser->id }}"
                    data-name="{{ $managedUser->name }}"
                    data-email="{{ $managedUser->email }}"
                    data-phone="{{ $managedUser->phone ?? '-' }}"
                    data-role="{{ $managedUser->role->value }}"
                    data-role-label="{{ $managedUser->role->label() }}"
                    data-status="{{ $managedUser->status }}"
                    data-status-label="{{ $managedUser->status === 'active' ? 'Aktif' : 'Nonaktif' }}"
                    data-created-at="{{ $managedUser->created_at->translatedFormat('d M Y') }}"
                    data-last-activity="{{ $managedUser->updated_at?->translatedFormat('d M Y H:i') ?? '-' }}"
                    data-update-url="{{ route('users.update', $managedUser) }}"
                    data-toggle-url="{{ route('users.toggle-status', $managedUser) }}"
                    data-reset-url="{{ route('users.reset-password', $managedUser) }}"
                    data-delete-url="{{ route('users.destroy', $managedUser) }}"
                >
                    <header>
                        <span class="mobile-user-card__avatar" aria-hidden="true">{{ $managedUser->initials() }}</span>
                        <div><h3>{{ $managedUser->name }}</h3><a href="mailto:{{ $managedUser->email }}">{{ $managedUser->email }}</a></div>
                        <button type="button" data-mobile-users-actions-open data-user-id="{{ $managedUser->id }}" aria-label="Buka aksi untuk {{ $managedUser->name }}"><i data-lucide="ellipsis-vertical" aria-hidden="true"></i></button>
                    </header>
                    <div class="mobile-user-card__meta">
                        <span class="mobile-users-badge mobile-users-badge--role-{{ $managedUser->role->value }}">{{ $managedUser->role->label() }}</span>
                        <span class="mobile-users-badge mobile-users-badge--status-{{ $managedUser->status }}">{{ $managedUser->status === 'active' ? 'Aktif' : 'Nonaktif' }}</span>
                        <span><i data-lucide="phone" aria-hidden="true"></i>{{ $managedUser->phone ?? '-' }}</span>
                    </div>
                    <footer>
                        <button type="button" data-mobile-users-detail-open data-user-id="{{ $managedUser->id }}"><i data-lucide="eye" aria-hidden="true"></i>Detail</button>
                        <button type="button" data-mobile-users-form-open data-user-id="{{ $managedUser->id }}"><i data-lucide="square-pen" aria-hidden="true"></i>Edit</button>
                    </footer>
                </article>
            @empty
            @endforelse
        </div>
        <div class="mobile-users-empty" data-mobile-users-empty @if ($users->isNotEmpty()) hidden @endif>
            <i data-lucide="user-round-search" aria-hidden="true"></i>
            <h3>User tidak ditemukan</h3>
            <p>Ubah filter atau gunakan kata pencarian lain.</p>
        </div>
        @if ($users->hasPages())
            <nav class="mobile-users-pagination" aria-label="Navigasi halaman user">
                @if ($users->onFirstPage())
                    <span aria-disabled="true">Sebelumnya</span>
                @else
                    <a href="{{ $users->previousPageUrl() }}">Sebelumnya</a>
                @endif
                <small>Halaman {{ $users->currentPage() }} dari {{ $users->lastPage() }}</small>
                @if ($users->hasMorePages())
                    <a href="{{ $users->nextPageUrl() }}">Berikutnya</a>
                @else
                    <span aria-disabled="true">Berikutnya</span>
                @endif
            </nav>
        @endif
    </section>

    <button class="mobile-users-fab" type="button" data-mobile-users-add aria-label="Tambah User"><i data-lucide="plus" aria-hidden="true"></i><span>Tambah User</span></button>

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
                <div class="mobile-users-detail-grid__wide"><dt>Terakhir diperbarui</dt><dd data-mobile-users-detail="lastActivity">-</dd></div>
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
            <form class="mobile-users-form" method="POST" action="{{ route('users.store') }}" data-mobile-users-form data-store-url="{{ route('users.store') }}">
                @csrf
                <input type="hidden" name="_method" value="PATCH" data-mobile-users-method disabled>
                <input type="hidden" name="context" value="mobile">
                <input type="hidden" name="editing_user_id" value="{{ old('editing_user_id') }}">
                <label class="mobile-users-field"><span>Nama lengkap</span><input type="text" name="name" value="{{ old('name') }}" placeholder="Masukkan nama lengkap" required>@error('name')<span class="form-error">{{ $message }}</span>@enderror</label>
                <label class="mobile-users-field"><span>Email</span><input type="email" name="email" value="{{ old('email') }}" placeholder="nama@contoh.com" required>@error('email')<span class="form-error">{{ $message }}</span>@enderror</label>
                <label class="mobile-users-field"><span>Nomor HP</span><input type="tel" name="phone" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx">@error('phone')<span class="form-error">{{ $message }}</span>@enderror</label>
                <div class="mobile-users-form__row">
                    <label class="mobile-users-field"><span>Role</span><select name="role" required>@foreach ($roles as $role)<option value="{{ $role->value }}" @selected(old('role', 'petugas') === $role->value)>{{ $role->label() }}</option>@endforeach</select>@error('role')<span class="form-error">{{ $message }}</span>@enderror</label>
                    <label class="mobile-users-field"><span>Status</span><select name="status" required><option value="active" @selected(old('status', 'active') === 'active')>Aktif</option><option value="inactive" @selected(old('status') === 'inactive')>Nonaktif</option></select>@error('status')<span class="form-error">{{ $message }}</span>@enderror</label>
                </div>
                <label class="mobile-users-field"><span>Password <small data-mobile-users-password-hint>(wajib)</small></span><span class="mobile-users-password"><input type="password" name="password" minlength="6" autocomplete="new-password"><button type="button" data-mobile-users-password-toggle aria-label="Tampilkan password"><i data-lucide="eye" aria-hidden="true"></i></button></span>@error('password')<span class="form-error">{{ $message }}</span>@enderror</label>
                <label class="mobile-users-field"><span>Konfirmasi password</span><input type="password" name="password_confirmation" minlength="6" autocomplete="new-password">@error('password_confirmation')<span class="form-error">{{ $message }}</span>@enderror</label>
                <p class="mobile-users-form__error" data-mobile-users-form-error role="alert" @if (!$errors->any()) hidden @endif>{{ $errors->first() }}</p>
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
                <form method="POST" data-mobile-users-toggle-form>
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="context" value="mobile">
                    <button type="submit" data-mobile-users-toggle-status><i data-lucide="user-round-cog" aria-hidden="true"></i><span><strong data-mobile-users-toggle-label>Ubah Status</strong><small>Aktifkan atau nonaktifkan akun</small></span><i data-lucide="chevron-right" aria-hidden="true"></i></button>
                </form>
                <form method="POST" data-mobile-users-reset-form>
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="context" value="mobile">
                    <button type="submit" data-mobile-users-reset-password><i data-lucide="key-round" aria-hidden="true"></i><span><strong>Reset Password</strong><small>Reset ke password default</small></span><i data-lucide="chevron-right" aria-hidden="true"></i></button>
                </form>
                <form method="POST" data-mobile-users-delete-form>
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="context" value="mobile">
                    <button class="mobile-users-actions__danger" type="submit" data-mobile-users-delete><i data-lucide="trash-2" aria-hidden="true"></i><span><strong>Hapus User</strong><small>Hapus akun dari sistem</small></span><i data-lucide="chevron-right" aria-hidden="true"></i></button>
                </form>
            </div>
        </article>
    </section>

    <div class="mobile-users-toast" role="status" aria-live="polite" data-mobile-users-toast @if (!session('success') && !session('error')) hidden @endif>
        <i data-lucide="circle-check" aria-hidden="true"></i>
        <p data-mobile-users-toast-message>{{ session('success') ?? session('error') ?? 'Aksi berhasil dilakukan.' }}</p>
        <button type="button" data-mobile-users-toast-close aria-label="Tutup notifikasi"><i data-lucide="x" aria-hidden="true"></i></button>
    </div>
</div>
