@extends('layouts.app')

@section('title', 'User Management')

@section('content')
    <div class="user-management-desktop-placeholder">
        @if (session('success'))
            <div class="alert alert--success" role="status">
                <i data-lucide="circle-check" aria-hidden="true"></i>
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert--danger" role="alert">
                <i data-lucide="triangle-alert" aria-hidden="true"></i>
                {{ session('error') }}
            </div>
        @endif

        <section class="layout-placeholder" aria-labelledby="user-management-desktop-title">
            <div class="layout-placeholder__heading">
                <div>
                    <p class="eyebrow">Administrasi akses</p>
                    <h2 id="user-management-desktop-title">User Management</h2>
                    <p>Kelola akun Admin, Petugas, dan Supervisor yang dapat menggunakan sistem.</p>
                </div>
                <a class="button button--primary" href="{{ route('users.create') }}">
                    <i data-lucide="user-plus" aria-hidden="true"></i>
                    Tambah User
                </a>
            </div>

            <div class="layout-placeholder__grid" aria-label="Ringkasan user">
                <article class="foundation-card"><small>Total User</small><strong>{{ $summary['total'] }}</strong></article>
                <article class="foundation-card"><small>Admin</small><strong>{{ $summary['admin'] }}</strong></article>
                <article class="foundation-card"><small>Petugas</small><strong>{{ $summary['petugas'] }}</strong></article>
                <article class="foundation-card"><small>Supervisor</small><strong>{{ $summary['supervisor'] }}</strong></article>
            </div>
        </section>

        <section class="panel" aria-labelledby="user-filter-title">
            <div class="layout-placeholder__heading">
                <div>
                    <p class="eyebrow">Pencarian data</p>
                    <h2 id="user-filter-title">Filter User</h2>
                </div>
            </div>
            <form method="GET" action="{{ route('users.index') }}">
                <div class="layout-placeholder__grid">
                    <div class="form-field">
                        <label for="user-search">Nama atau email</label>
                        <input class="form-control" id="user-search" name="search" type="search" value="{{ $filters['search'] }}" placeholder="Cari nama atau email">
                    </div>
                    <div class="form-field">
                        <label for="user-role">Role</label>
                        <select class="form-control" id="user-role" name="role">
                            <option value="">Semua role</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->value }}" @selected($filters['role'] === $role->value)>{{ $role->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="user-status">Status</label>
                        <select class="form-control" id="user-status" name="status">
                            <option value="">Semua status</option>
                            <option value="active" @selected($filters['status'] === 'active')>Aktif</option>
                            <option value="inactive" @selected($filters['status'] === 'inactive')>Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div>
                    <button class="button button--primary" type="submit"><i data-lucide="search" aria-hidden="true"></i>Terapkan</button>
                    <a class="button button--neutral" href="{{ route('users.index') }}">Reset</a>
                </div>
            </form>
        </section>

        <section class="table-panel" aria-labelledby="user-list-title">
            <div class="layout-placeholder__heading">
                <div>
                    <p class="eyebrow">Daftar akses sistem</p>
                    <h2 id="user-list-title">Data User</h2>
                </div>
                <span class="status-badge status-badge--verified">{{ $users->total() }} user</span>
            </div>

            <div class="machines-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Nomor HP</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $managedUser)
                            <tr>
                                <td><strong>{{ $managedUser->name }}</strong></td>
                                <td>{{ $managedUser->email }}</td>
                                <td>{{ $managedUser->role->label() }}</td>
                                <td>{{ $managedUser->phone ?: '-' }}</td>
                                <td>
                                    <span @class([
                                        'status-badge',
                                        'status-badge--verified' => $managedUser->status === 'active',
                                        'status-badge--neutral' => $managedUser->status === 'inactive',
                                    ])>{{ $managedUser->status === 'active' ? 'Aktif' : 'Nonaktif' }}</span>
                                </td>
                                <td>
                                    <div>
                                        <a class="button button--neutral" href="{{ route('users.show', $managedUser) }}">Detail</a>
                                        <a class="button button--secondary" href="{{ route('users.edit', $managedUser) }}">Edit</a>
                                        <form method="POST" action="{{ route('users.toggle-status', $managedUser) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="button button--neutral" type="submit" @disabled(auth()->id() === $managedUser->id && $managedUser->status === 'active')>
                                                {{ $managedUser->status === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('users.reset-password', $managedUser) }}" onsubmit="return confirm('Reset password user ke password default?')">
                                            @csrf
                                            @method('PATCH')
                                            <button class="button button--neutral" type="submit">Reset Password</button>
                                        </form>
                                        <form method="POST" action="{{ route('users.destroy', $managedUser) }}" onsubmit="return confirm('Hapus user ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="button button--neutral" type="submit" @disabled(auth()->id() === $managedUser->id)>Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6">Tidak ada user yang sesuai dengan filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <nav aria-label="Pagination user">
                    @if ($users->onFirstPage())
                        <span class="button button--neutral" aria-disabled="true">Sebelumnya</span>
                    @else
                        <a class="button button--neutral" href="{{ $users->previousPageUrl() }}" rel="prev">Sebelumnya</a>
                    @endif
                    <span>Halaman {{ $users->currentPage() }} dari {{ $users->lastPage() }}</span>
                    @if ($users->hasMorePages())
                        <a class="button button--neutral" href="{{ $users->nextPageUrl() }}" rel="next">Berikutnya</a>
                    @else
                        <span class="button button--neutral" aria-disabled="true">Berikutnya</span>
                    @endif
                </nav>
            @endif
        </section>
    </div>
@endsection
