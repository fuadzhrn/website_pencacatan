@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
    <section class="panel" aria-labelledby="edit-user-title">
        <div class="layout-placeholder__heading">
            <div>
                <p class="eyebrow">User Management</p>
                <h2 id="edit-user-title">Edit User</h2>
                <p>Perbarui profil dan hak akses {{ $managedUser->name }}.</p>
            </div>
            <a class="button button--neutral" href="{{ route('users.index') }}">Kembali</a>
        </div>

        @if ($errors->any())
            <div class="alert alert--danger" role="alert">
                <i data-lucide="triangle-alert" aria-hidden="true"></i>
                <div><strong>Data belum dapat disimpan.</strong><ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
            </div>
        @endif

        <form method="POST" action="{{ route('users.update', $managedUser) }}">
            @csrf
            @method('PUT')
            <div class="layout-placeholder__grid">
                <div class="form-field">
                    <label for="name">Nama lengkap</label>
                    <input class="form-control" id="name" name="name" type="text" value="{{ old('name', $managedUser->name) }}" maxlength="255" required autofocus>
                    @error('name')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-field">
                    <label for="email">Email</label>
                    <input class="form-control" id="email" name="email" type="email" value="{{ old('email', $managedUser->email) }}" maxlength="255" required>
                    @error('email')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-field">
                    <label for="phone">Nomor HP <small>(opsional)</small></label>
                    <input class="form-control" id="phone" name="phone" type="tel" value="{{ old('phone', $managedUser->phone) }}" maxlength="30">
                    @error('phone')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-field">
                    <label for="role">Role</label>
                    <select class="form-control" id="role" name="role" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role->value }}" @selected(old('role', $managedUser->role->value) === $role->value)>{{ $role->label() }}</option>
                        @endforeach
                    </select>
                    @error('role')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-field">
                    <label for="status">Status</label>
                    <select class="form-control" id="status" name="status" required>
                        <option value="active" @selected(old('status', $managedUser->status) === 'active')>Aktif</option>
                        <option value="inactive" @selected(old('status', $managedUser->status) === 'inactive') @disabled(auth()->id() === $managedUser->id)>Nonaktif</option>
                    </select>
                    @error('status')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-field">
                    <label for="password">Password baru <small>(opsional)</small></label>
                    <input class="form-control" id="password" name="password" type="password" minlength="6" autocomplete="new-password">
                    @error('password')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-field">
                    <label for="password_confirmation">Konfirmasi password baru</label>
                    <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" minlength="6" autocomplete="new-password">
                </div>
            </div>
            <div>
                <a class="button button--neutral" href="{{ route('users.show', $managedUser) }}">Batal</a>
                <button class="button button--primary" type="submit"><i data-lucide="save" aria-hidden="true"></i>Simpan Perubahan</button>
            </div>
        </form>
    </section>
@endsection
