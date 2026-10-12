@extends('layouts.app')

@section('title', 'Detail Mesin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/machines.css') }}">
@endpush

@section('content')
    <section class="machines-table-panel machine-record-page" aria-labelledby="machine-detail-title">
        <header class="machines-table-panel__header">
            <div><h2 id="machine-detail-title">Detail Mesin</h2><p>Informasi inventaris dan riwayat penggunaan mesin.</p></div>
            <div class="machine-record-header-actions"><a class="button button--neutral" href="{{ route('machines.index') }}">Kembali</a><a class="button button--primary" href="{{ route('machines.edit', $machine) }}">Edit Mesin</a></div>
        </header>

        @if (session('success'))<div class="alert alert--success machine-record-alert" role="status">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="alert alert--danger machine-record-alert" role="alert">{{ session('error') }}</div>@endif

        <div class="machine-detail__hero">
            <span aria-hidden="true"><i data-lucide="scan-line"></i></span>
            <div><p>Nama peralatan</p><h3>{{ $machine->equipment_name }}</h3><code>{{ $machine->serial_number }}</code></div>
            <span class="machine-status machine-status--{{ $machine->status }}">{{ $statusOptions[$machine->status] }}</span>
        </div>
        <dl class="machine-detail__grid machine-detail__grid--page">
            <div><dt>Merk</dt><dd>{{ $machine->brand }}</dd></div>
            <div><dt>Tipe</dt><dd>{{ $machine->type }}</dd></div>
            <div><dt>Lokasi</dt><dd>{{ $machine->location }}</dd></div>
            <div><dt>Bandar Udara</dt><dd>{{ $machine->airport }}</dd></div>
            <div><dt>Jadwal Maintenance</dt><dd>{{ $machine->schedules_count }}</dd></div>
            <div><dt>Pemeriksaan</dt><dd>{{ $machine->checks_count }}</dd></div>
            <div><dt>Temuan</dt><dd>{{ $machine->findings_count }}</dd></div>
            <div><dt>Tanggal Dibuat</dt><dd>{{ $machine->created_at->format('d M Y H:i') }}</dd></div>
            <div class="machine-detail__wide"><dt>Deskripsi</dt><dd>{{ $machine->description ?: '-' }}</dd></div>
        </dl>
        <footer class="machine-record-actions">
            <form method="POST" action="{{ route('machines.toggle-status', $machine) }}">@csrf @method('PATCH')<button class="button button--secondary" type="submit">{{ $machine->status === 'active' ? 'Nonaktifkan Mesin' : 'Aktifkan Mesin' }}</button></form>
            <form method="POST" action="{{ route('machines.destroy', $machine) }}" onsubmit="return confirm('Hapus mesin ini?')">@csrf @method('DELETE')<button class="button button--neutral" type="submit">Hapus Mesin</button></form>
        </footer>
    </section>
@endsection
