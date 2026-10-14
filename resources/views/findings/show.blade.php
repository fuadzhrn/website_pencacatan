@extends('layouts.app')

@section('title', 'Detail Temuan')

@push('styles')<link rel="stylesheet" href="{{ asset('assets/css/findings.css') }}">@endpush

@section('content')
<section class="findings-panel finding-detail-page">
    <header class="findings-panel__header"><div><span>Detail Temuan</span><h2>{{ $finding->item?->item_name ?? 'Item pemeriksaan' }}</h2><p>{{ $finding->machine->equipment_name }} — {{ $finding->machine->location }}</p></div><span class="finding-badge finding-badge--{{ $finding->status }}">{{ $statusLabels[$finding->status] }}</span></header>
    <dl class="finding-detail-grid">
        <div><dt>Tanggal temuan</dt><dd>{{ $finding->finding_date->translatedFormat('d F Y') }}</dd></div><div><dt>Petugas</dt><dd>{{ $finding->reportedBy->name }}</dd></div>
        <div><dt>Kriteria</dt><dd>{{ $finding->item?->criteria ?? '-' }}</dd></div><div><dt>Keterangan</dt><dd>{{ $finding->finding_description }}</dd></div>
        <div><dt>Tindak lanjut</dt><dd>{{ $finding->follow_up ?: '-' }}</dd></div><div><dt>Ditinjau oleh</dt><dd>{{ $finding->reviewedBy?->name ?? '-' }}</dd></div>
    </dl>
    @if ($finding->photo)<img src="{{ asset('storage/'.$finding->photo) }}" alt="Foto temuan {{ $finding->item?->item_name }}" class="finding-detail-photo">@endif
    @if (auth()->user()->role === \App\UserRole::Petugas && $finding->reported_by === auth()->id() && $finding->status === 'open' && $finding->reviewed_by === null)
        <form method="POST" action="{{ route('findings.update', $finding) }}" enctype="multipart/form-data" class="findings-form">
            @csrf @method('PUT')
            <input type="hidden" name="maintenance_check_id" value="{{ $finding->maintenance_check_id }}"><input type="hidden" name="maintenance_check_detail_id" value="{{ $finding->maintenance_check_detail_id }}"><input type="hidden" name="machine_id" value="{{ $finding->machine_id }}"><input type="hidden" name="maintenance_item_id" value="{{ $finding->maintenance_item_id }}"><input type="hidden" name="status" value="open">
            <label><span>Keterangan temuan</span><textarea name="finding_description" rows="3" required>{{ old('finding_description', $finding->finding_description) }}</textarea></label>
            <label><span>Tindak lanjut</span><textarea name="follow_up" rows="3">{{ old('follow_up', $finding->follow_up) }}</textarea></label>
            <label><span>Ganti foto (opsional)</span><input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label>
            <button type="submit" class="findings-button findings-button--primary">Perbarui Temuan</button>
        </form>
    @endif
    @if (auth()->user()->role === \App\UserRole::Supervisor && $finding->status !== 'resolved')
        <form method="POST" action="{{ route('findings.update-status', $finding) }}" class="findings-form">@csrf @method('PATCH')<input type="hidden" name="status" value="{{ $finding->status === 'open' ? 'review' : 'resolved' }}"><button type="submit" class="findings-button findings-button--primary">{{ $finding->status === 'open' ? 'Tandai Review' : 'Selesaikan Temuan' }}</button></form>
    @endif
    <a href="{{ route('findings.index') }}" class="findings-button findings-button--soft">Kembali</a>
</section>
@endsection
