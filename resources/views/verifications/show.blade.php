@extends('layouts.app')

@section('title', 'Detail Verifikasi Pemeriksaan')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/verifications.css') }}">
@endpush

@section('content')
<div class="verification-page verification-detail">
    @if (session('success'))<div class="verification-alert verification-alert--success" role="status">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="verification-alert verification-alert--error" role="alert">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="verification-alert verification-alert--error" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="verification-detail__toolbar">
        <a href="{{ route('verifications.index') }}" class="verification-button verification-button--soft"><i data-lucide="arrow-left" aria-hidden="true"></i>Kembali</a>
        <span class="verification-badge verification-badge--{{ $check->verification_status }}">{{ $verificationLabels[$check->verification_status] ?? $check->verification_status }}</span>
    </div>

    <section class="verification-panel verification-detail__hero">
        <div><span>Mesin X-Ray</span><h2>{{ $check->machine->equipment_name }}</h2><p><i data-lucide="map-pin" aria-hidden="true"></i>{{ $check->machine->location }} · {{ $check->machine->airport }}</p></div>
        <dl>
            <div><dt>Merk</dt><dd>{{ $check->machine->brand }}</dd></div><div><dt>Tipe</dt><dd>{{ $check->machine->type }}</dd></div>
            <div><dt>Serial Number</dt><dd>{{ $check->machine->serial_number }}</dd></div><div><dt>Frekuensi</dt><dd>{{ $check->schedule?->maintenanceCategory?->name ?? '-' }}</dd></div>
            <div><dt>Tanggal</dt><dd>{{ $check->check_date->translatedFormat('d F Y') }}</dd></div><div><dt>Petugas</dt><dd>{{ $check->checkedBy->name }}</dd></div>
            <div><dt>Waktu</dt><dd>{{ $check->start_time }} - {{ $check->end_time }}</dd></div><div><dt>Hasil</dt><dd>{{ $overallLabels[$check->overall_status] ?? $check->overall_status }}</dd></div>
        </dl>
        <div class="verification-note"><strong>Catatan Pemeriksaan</strong><p>{{ $check->general_note ?: 'Tidak ada catatan.' }}</p></div>
    </section>

    <section class="verification-panel">
        <header class="verification-panel__header"><div><span>Hasil per item</span><h2>Checklist Pemeriksaan</h2></div></header>
        <div class="verification-table-wrap"><table class="verification-table"><thead><tr><th>Kategori</th><th>Item Pemeriksaan</th><th>Kriteria</th><th>Hasil</th><th>Nilai Aktual</th><th>Catatan</th></tr></thead><tbody>
            @forelse ($check->details as $detail)<tr><td>{{ $detail->item?->category?->name ?? '-' }}</td><td><strong>{{ $detail->item?->item_name ?? '-' }}</strong></td><td>{{ $detail->item?->criteria ?? '-' }}</td><td><span class="verification-badge verification-badge--{{ $detail->result_status === 'sesuai' ? 'verified' : 'rejected' }}">{{ $detail->result_status === 'sesuai' ? 'Sesuai' : 'Tidak Sesuai' }}</span></td><td>{{ $detail->actual_value ?: '-' }}</td><td>{{ $detail->note ?: '-' }}</td></tr>@empty<tr><td colspan="6">Belum ada detail checklist.</td></tr>@endforelse
        </tbody></table></div>
    </section>

    <section class="verification-panel">
        <header class="verification-panel__header"><div><span>Tindak lanjut</span><h2>Temuan Pemeriksaan</h2></div></header>
        <div class="verification-findings">@forelse ($check->findings as $finding)<article><div><span class="verification-badge verification-badge--{{ $finding->status === 'resolved' ? 'verified' : 'rejected' }}">{{ ucfirst($finding->status) }}</span><h3>{{ $finding->item?->item_name ?? 'Item pemeriksaan' }}</h3></div><p>{{ $finding->finding_description }}</p><small><strong>Tindak lanjut:</strong> {{ $finding->follow_up ?: 'Belum ada.' }}</small>@if ($finding->photo)<a href="{{ asset('storage/'.$finding->photo) }}" target="_blank" rel="noopener">Lihat foto temuan</a>@endif</article>@empty<div class="verification-empty"><i data-lucide="badge-check" aria-hidden="true"></i><strong>Tidak ada temuan</strong><p>Seluruh item pemeriksaan tercatat sesuai.</p></div>@endforelse</div>
    </section>

    @if ($check->verification_status === 'waiting' && auth()->user()->role === \App\UserRole::Supervisor)
        <section class="verification-panel verification-decision">
            <header class="verification-panel__header"><div><span>Keputusan Supervisor</span><h2>Verifikasi Hasil Pemeriksaan</h2><p>Catatan wajib diisi untuk keputusan tolak/revisi.</p></div></header>
            <form method="POST" class="verification-decision__form" data-verification-decision-form>
                @csrf @method('PATCH')
                <label><span>Catatan Supervisor</span><textarea name="supervisor_note" rows="4" maxlength="5000" placeholder="Tuliskan hasil tinjauan atau instruksi revisi...">{{ old('supervisor_note') }}</textarea></label>
                <div><button type="submit" formaction="{{ route('verifications.reject', $check) }}" class="verification-button verification-button--danger"><i data-lucide="circle-x" aria-hidden="true"></i>Tolak / Revisi</button><button type="submit" formaction="{{ route('verifications.verify', $check) }}" class="verification-button verification-button--primary"><i data-lucide="badge-check" aria-hidden="true"></i>Verifikasi</button></div>
            </form>
        </section>
    @else
        <section class="verification-panel verification-note"><strong>Catatan Supervisor</strong><p>{{ $check->supervisor_note ?: 'Belum ada catatan supervisor.' }}</p>@if ($check->verifiedBy)<small>Diproses oleh {{ $check->verifiedBy->name }} pada {{ $check->verified_at?->translatedFormat('d M Y H:i') }}</small>@endif</section>
    @endif
</div>
@endsection
