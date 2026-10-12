@extends('layouts.app')

@section('title', 'Detail User')

@section('content')
    <section class="panel" aria-labelledby="user-detail-title">
        <div class="layout-placeholder__heading">
            <div>
                <p class="eyebrow">User Management</p>
                <h2 id="user-detail-title">Detail User</h2>
                <p>Informasi akun dan hak akses pengguna sistem.</p>
            </div>
            <div>
                <a class="button button--neutral" href="{{ route('users.index') }}">Kembali</a>
                <a class="button button--primary" href="{{ route('users.edit', $managedUser) }}">Edit User</a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert--success" role="status">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert--danger" role="alert">{{ session('error') }}</div>
        @endif

        <dl class="layout-placeholder__grid">
            <div class="foundation-card"><dt>Nama lengkap</dt><dd>{{ $managedUser->name }}</dd></div>
            <div class="foundation-card"><dt>Email</dt><dd>{{ $managedUser->email }}</dd></div>
            <div class="foundation-card"><dt>Nomor HP</dt><dd>{{ $managedUser->phone ?: '-' }}</dd></div>
            <div class="foundation-card"><dt>Role</dt><dd>{{ $managedUser->role->label() }}</dd></div>
            <div class="foundation-card"><dt>Status</dt><dd>{{ $managedUser->status === 'active' ? 'Aktif' : 'Nonaktif' }}</dd></div>
            <div class="foundation-card"><dt>Dibuat</dt><dd>{{ $managedUser->created_at->format('d M Y H:i') }}</dd></div>
        </dl>

        <div>
            <form method="POST" action="{{ route('users.toggle-status', $managedUser) }}">
                @csrf
                @method('PATCH')
                <button class="button button--secondary" type="submit" @disabled(auth()->id() === $managedUser->id && $managedUser->status === 'active')>
                    {{ $managedUser->status === 'active' ? 'Nonaktifkan User' : 'Aktifkan User' }}
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
                <button class="button button--neutral" type="submit" @disabled(auth()->id() === $managedUser->id)>Hapus User</button>
            </form>
        </div>
    </section>
@endsection
