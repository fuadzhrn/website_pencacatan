@extends('layouts.app')

@section('title', $isVerificationQueue ? 'Verifikasi Pemeriksaan' : 'Riwayat Pemeriksaan')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/verifications.css') }}">
@endpush

@section('content')
@php
    $indexRoute = $isVerificationQueue ? route('verifications.index') : route('inspections.history');
@endphp
<div class="verification-page">
    @if (session('success'))
        <div class="verification-alert verification-alert--success" role="status">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="verification-alert verification-alert--error" role="alert">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="verification-alert verification-alert--error" role="alert">
            <strong>Filter belum dapat diterapkan.</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="verification-summary" aria-label="Ringkasan pemeriksaan">
        @foreach (['total' => 'Total Pemeriksaan', 'waiting' => 'Menunggu Verifikasi', 'verified' => 'Terverifikasi', 'rejected' => 'Ditolak/Revisi'] as $key => $label)
            <article class="verification-summary__card verification-summary__card--{{ $key }}">
                <span><i data-lucide="{{ $key === 'verified' ? 'badge-check' : ($key === 'rejected' ? 'circle-x' : ($key === 'waiting' ? 'clock-3' : 'clipboard-list')) }}" aria-hidden="true"></i></span>
                <div><small>{{ $label }}</small><strong>{{ $summary[$key] }}</strong></div>
            </article>
        @endforeach
    </section>

    <section class="verification-panel">
        <header class="verification-panel__header">
            <div>
                <span>{{ $isVerificationQueue ? 'Persetujuan Supervisor' : 'Dokumentasi Pemeriksaan' }}</span>
                <h2>{{ $isVerificationQueue ? 'Daftar Verifikasi Pemeriksaan' : 'Riwayat Pemeriksaan' }}</h2>
                <p>{{ $isVerificationQueue ? 'Tinjau hasil checklist dan temuan sebelum memberi keputusan.' : 'Data berasal dari pemeriksaan yang telah diselesaikan.' }}</p>
            </div>
        </header>

        <form method="GET" action="{{ $indexRoute }}" class="verification-filter">
            <label><span>Cari</span><input type="search" name="search" value="{{ request('search') }}" placeholder="Mesin, serial, atau petugas"></label>
            <label><span>Tanggal mulai</span><input type="date" name="start_date" value="{{ request('start_date') }}"></label>
            <label><span>Tanggal akhir</span><input type="date" name="end_date" value="{{ request('end_date') }}"></label>
            <label><span>Mesin</span><select name="machine_id"><option value="">Semua mesin</option>@foreach ($machines as $machine)<option value="{{ $machine->id }}" @selected((string) request('machine_id') === (string) $machine->id)>{{ $machine->equipment_name }}</option>@endforeach</select></label>
            <label><span>Petugas</span><select name="officer_id"><option value="">Semua petugas</option>@foreach ($officers as $officer)<option value="{{ $officer->id }}" @selected((string) request('officer_id') === (string) $officer->id)>{{ $officer->name }}</option>@endforeach</select></label>
            <label><span>Status verifikasi</span><select name="verification_status"><option value="">{{ $isVerificationQueue ? 'Menunggu (default)' : 'Semua status' }}</option>@foreach ($verificationLabels as $value => $label)<option value="{{ $value }}" @selected(request('verification_status') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label><span>Hasil</span><select name="overall_status"><option value="">Semua hasil</option>@foreach ($overallLabels as $value => $label)<option value="{{ $value }}" @selected(request('overall_status') === $value)>{{ $label }}</option>@endforeach</select></label>
            <div class="verification-filter__actions"><a href="{{ $indexRoute }}" class="verification-button verification-button--soft">Reset</a><button class="verification-button verification-button--primary" type="submit">Terapkan</button></div>
        </form>

        <div class="verification-table-wrap">
            <table class="verification-table">
                <thead><tr><th>Mesin / Lokasi</th><th>Tanggal</th><th>Frekuensi</th><th>Petugas</th><th>Hasil</th><th>Verifikasi</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse ($checks as $check)
                        <tr>
                            <td><strong>{{ $check->machine->equipment_name }}</strong><small>{{ $check->machine->location }} · {{ $check->machine->serial_number }}</small></td>
                            <td>{{ $check->check_date->translatedFormat('d M Y') }}<small>{{ $check->start_time }} - {{ $check->end_time }}</small></td>
                            <td>{{ $check->schedule?->maintenanceCategory?->name ?? '-' }}</td>
                            <td>{{ $check->checkedBy->name }}</td>
                            <td><span class="verification-badge verification-badge--{{ $check->overall_status }}">{{ $overallLabels[$check->overall_status] ?? $check->overall_status }}</span></td>
                            <td><span class="verification-badge verification-badge--{{ $check->verification_status }}">{{ $verificationLabels[$check->verification_status] ?? $check->verification_status }}</span></td>
                            <td>
                                @if (auth()->user()->role === \App\UserRole::Petugas)
                                    <a class="verification-button verification-button--soft" href="{{ route('inspections.show', $check) }}">Detail</a>
                                @else
                                    <a class="verification-button verification-button--soft" href="{{ route('verifications.show', $check) }}">Detail</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="verification-empty"><i data-lucide="clipboard-x" aria-hidden="true"></i><strong>Data tidak ditemukan</strong><p>Tidak ada pemeriksaan yang sesuai dengan filter.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
