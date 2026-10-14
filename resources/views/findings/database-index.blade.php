@extends('layouts.app')

@section('title', 'Temuan Pemeriksaan')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/findings.css') }}">
@endpush

@section('content')
<div class="findings-page">
    @if (session('success'))<div class="findings-alert findings-alert--success" role="status">{{ session('success') }}</div>@endif
    @if ($errors->any())
        <div class="findings-alert findings-alert--error" role="alert"><strong>Data belum dapat disimpan.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <section class="findings-summary" aria-label="Ringkasan temuan">
        @foreach (['total' => 'Total Temuan', 'open' => 'Open', 'review' => 'Review', 'resolved' => 'Resolved'] as $key => $label)
            <article class="findings-summary__card findings-summary__card--{{ $key }}"><span><i class="ri-error-warning-line" aria-hidden="true"></i></span><div><small>{{ $label }}</small><strong>{{ $summary[$key] }}</strong></div></article>
        @endforeach
    </section>

    <section class="findings-panel">
        <header class="findings-panel__header"><div><span>Monitoring</span><h2>Daftar Temuan</h2><p>Temuan berasal dari hasil pemeriksaan yang tidak sesuai.</p></div></header>
        <form method="GET" action="{{ route('findings.index') }}" class="findings-filter">
            <label><span>Cari</span><input type="search" name="search" value="{{ request('search') }}" placeholder="Mesin, item, atau keterangan"></label>
            <label><span>Mesin</span><select name="machine_id"><option value="">Semua mesin</option>@foreach ($machines as $machine)<option value="{{ $machine->id }}" @selected((string) request('machine_id') === (string) $machine->id)>{{ $machine->equipment_name }}</option>@endforeach</select></label>
            <label><span>Status</span><select name="status"><option value="">Semua status</option>@foreach ($statusLabels as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label><span>Tanggal</span><input type="date" name="date" value="{{ request('date') }}"></label>
            <div class="findings-filter__actions"><a href="{{ route('findings.index') }}" class="findings-button findings-button--soft">Reset</a><button class="findings-button findings-button--primary" type="submit">Terapkan</button></div>
        </form>

        @if (auth()->user()->role === \App\UserRole::Petugas && $availableDetails->isNotEmpty())
            <details class="findings-create">
                <summary><i class="ri-add-line" aria-hidden="true"></i> Tambah Temuan Manual</summary>
                <form method="POST" action="{{ route('findings.store') }}" enctype="multipart/form-data" class="findings-form">
                    @csrf
                    <label><span>Item tidak sesuai</span><select name="maintenance_check_detail_id" required data-finding-detail-select><option value="">Pilih item</option>@foreach ($availableDetails as $detail)<option value="{{ $detail->id }}" data-check="{{ $detail->maintenance_check_id }}" data-machine="{{ $detail->check->machine_id }}" data-item="{{ $detail->maintenance_item_id }}">{{ $detail->check->machine->equipment_name }} — {{ $detail->item->item_name }}</option>@endforeach</select></label>
                    <input type="hidden" name="maintenance_check_id" data-finding-check><input type="hidden" name="machine_id" data-finding-machine><input type="hidden" name="maintenance_item_id" data-finding-item><input type="hidden" name="status" value="open">
                    <label><span>Keterangan</span><textarea name="finding_description" rows="3" required>{{ old('finding_description') }}</textarea></label>
                    <label><span>Tindak lanjut</span><textarea name="follow_up" rows="3">{{ old('follow_up') }}</textarea></label>
                    <label><span>Foto (opsional, maks. 2 MB)</span><input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label>
                    <button type="submit" class="findings-button findings-button--primary">Simpan Temuan</button>
                </form>
            </details>
        @endif

        <div class="findings-table-wrap"><table class="findings-table">
            <thead><tr><th>Mesin / Lokasi</th><th>Item</th><th>Keterangan</th><th>Tanggal</th><th>Petugas</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>@forelse ($findings as $finding)
                <tr>
                    <td><strong>{{ $finding->machine->equipment_name }}</strong><small>{{ $finding->machine->location }}</small></td>
                    <td>{{ $finding->item?->item_name ?? '-' }}</td>
                    <td>{{ $finding->finding_description }}<small>{{ $finding->follow_up ?: 'Belum ada tindak lanjut.' }}</small></td>
                    <td>{{ $finding->finding_date->translatedFormat('d M Y') }}</td><td>{{ $finding->reportedBy->name }}</td>
                    <td><span class="finding-badge finding-badge--{{ $finding->status }}">{{ $statusLabels[$finding->status] }}</span></td>
                    <td class="findings-table__actions"><a href="{{ route('findings.show', $finding) }}" class="findings-button findings-button--soft">Detail</a>
                        @if (auth()->user()->role === \App\UserRole::Supervisor && $finding->status !== 'resolved')<form method="POST" action="{{ route('findings.update-status', $finding) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $finding->status === 'open' ? 'review' : 'resolved' }}"><button class="findings-button findings-button--primary" type="submit">{{ $finding->status === 'open' ? 'Review' : 'Selesaikan' }}</button></form>@endif
                    </td>
                </tr>
            @empty<tr><td colspan="7"><div class="findings-empty"><strong>Belum ada temuan</strong><p>Tidak ada data yang sesuai dengan filter.</p></div></td></tr>@endforelse</tbody>
        </table></div>
    </section>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/findings-database.js') }}" defer></script>
@endpush
