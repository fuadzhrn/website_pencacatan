@extends('layouts.app')

@section('title', 'Detail Temuan')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/findings.css') }}">
@endpush

@section('content')
    @php
        $itemName = $finding->item?->item_name ?? 'Item pemeriksaan';
        $canEditFinding = auth()->user()->role === \App\UserRole::Petugas
            && $finding->reported_by === auth()->id()
            && $finding->status === 'open'
            && $finding->reviewed_by === null;
        $canChangeStatus = auth()->user()->role === \App\UserRole::Supervisor && $finding->status !== 'resolved';
        $nextStatus = $finding->status === 'open' ? 'review' : 'resolved';
    @endphp

    <div class="findings-page">
        <header class="findings-page-header">
            <div>
                <p class="findings-page-header__eyebrow">Detail Temuan</p>
                <h2>{{ $itemName }}</h2>
                <p>{{ $finding->machine->equipment_name }} &mdash; {{ $finding->machine->location }}</p>
            </div>
            <a class="button button--secondary" href="{{ route('findings.index') }}"><i data-lucide="arrow-left" aria-hidden="true"></i>Kembali</a>
        </header>

        <section class="findings-table-panel" aria-labelledby="finding-detail-title">
            <div class="findings-dialog__body">
                <div class="finding-detail-hero">
                    <div><span>Item Tidak Sesuai</span><h4 id="finding-detail-title">{{ $itemName }}</h4><p>{{ $finding->machine->equipment_name }} &middot; {{ $finding->machine->serial_number }}</p></div>
                    <span class="status-badge status-badge--{{ $finding->status }}"><span aria-hidden="true"></span>{{ $statusLabels[$finding->status] ?? $finding->status }}</span>
                </div>
                <dl class="finding-detail-grid">
                    <div><dt>Tanggal Temuan</dt><dd>{{ $finding->finding_date->translatedFormat('d F Y') }}</dd></div>
                    <div><dt>Lokasi</dt><dd>{{ $finding->machine->location }} &middot; {{ $finding->machine->airport }}</dd></div>
                    <div><dt>Frekuensi</dt><dd>{{ $frequencyLabels[$finding->item?->category?->frequency] ?? '-' }}</dd></div>
                    <div><dt>Petugas</dt><dd>{{ $finding->reportedBy->name }}</dd></div>
                    <div><dt>Ditinjau Oleh</dt><dd>{{ $finding->reviewedBy?->name ?? '-' }}@if ($finding->reviewed_at) &middot; {{ $finding->reviewed_at->translatedFormat('d M Y H:i') }}@endif</dd></div>
                    <div><dt>Diselesaikan</dt><dd>{{ $finding->resolved_at?->translatedFormat('d M Y H:i') ?? '-' }}</dd></div>
                    <div class="finding-detail-grid__wide"><dt>Kriteria Pemeriksaan</dt><dd>{{ $finding->item?->criteria ?? '-' }}</dd></div>
                    <div class="finding-detail-grid__wide"><dt>Keterangan Temuan</dt><dd>{{ $finding->finding_description }}</dd></div>
                    <div class="finding-detail-grid__wide"><dt>Tindak Lanjut</dt><dd>{{ $finding->follow_up ?: 'Belum ada tindak lanjut.' }}</dd></div>
                </dl>
                @if ($finding->photo)
                    <a class="finding-detail-photo" href="{{ route('findings.photo', $finding) }}" target="_blank" rel="noopener">
                        <span class="finding-photo" aria-hidden="true"><img src="{{ route('findings.photo', $finding) }}" alt="" loading="lazy"></span>
                        <div><strong>Foto dokumentasi</strong><small>Buka foto ukuran penuh di tab baru</small></div>
                    </a>
                @else
                    <div class="finding-detail-photo finding-detail-photo--empty"><i data-lucide="image-off" aria-hidden="true"></i><span>Foto dokumentasi tidak tersedia.</span></div>
                @endif
            </div>
        </section>

        @if ($canEditFinding)
            <section class="findings-filter-panel finding-detail-actions" id="finding-edit" aria-labelledby="finding-edit-title">
                <div class="findings-section-heading">
                    <div>
                        <span class="findings-section-heading__icon" aria-hidden="true"><i data-lucide="file-pen-line"></i></span>
                        <div><h3 id="finding-edit-title">Perbarui Temuan</h3><p>Temuan dapat diubah selama belum ditinjau supervisor.</p></div>
                    </div>
                </div>
                <form method="POST" action="{{ route('findings.update', $finding) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="maintenance_check_id" value="{{ $finding->maintenance_check_id }}">
                    <input type="hidden" name="maintenance_check_detail_id" value="{{ $finding->maintenance_check_detail_id }}">
                    <input type="hidden" name="machine_id" value="{{ $finding->machine_id }}">
                    <input type="hidden" name="maintenance_item_id" value="{{ $finding->maintenance_item_id }}">
                    <input type="hidden" name="status" value="open">
                    <div class="findings-form-grid">
                        <label class="field findings-form-grid__wide"><span>Keterangan Temuan</span><textarea name="finding_description" rows="3" maxlength="5000" required>{{ old('finding_description', $finding->finding_description) }}</textarea>@error('finding_description')<span class="form-error">{{ $message }}</span>@enderror</label>
                        <label class="field findings-form-grid__wide"><span>Tindak Lanjut <small>(opsional)</small></span><textarea name="follow_up" rows="3" maxlength="5000">{{ old('follow_up', $finding->follow_up) }}</textarea>@error('follow_up')<span class="form-error">{{ $message }}</span>@enderror</label>
                        <label class="field findings-form-grid__wide"><span>Ganti Foto <small>(opsional, maks. 2 MB)</small></span><span class="finding-upload"><input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-photo-input><span class="finding-upload__placeholder" data-upload-placeholder><i data-lucide="image-up" aria-hidden="true"></i><strong>Pilih foto dokumentasi</strong><small>JPG, PNG, atau WEBP</small></span><img src="" alt="Preview foto temuan" data-upload-preview hidden></span>@error('photo')<span class="form-error">{{ $message }}</span>@enderror</label>
                    </div>
                    <div class="finding-detail-actions__footer"><button class="button button--primary" type="submit"><i data-lucide="save" aria-hidden="true"></i>Perbarui Temuan</button></div>
                </form>
            </section>
        @endif

        @if ($canChangeStatus)
            <section class="findings-filter-panel finding-detail-actions" aria-labelledby="finding-status-title">
                <div class="findings-section-heading">
                    <div>
                        <span class="findings-section-heading__icon" aria-hidden="true"><i data-lucide="refresh-cw"></i></span>
                        <div><h3 id="finding-status-title">Proses Status Temuan</h3><p>Status saat ini {{ $statusLabels[$finding->status] }}. Tahap berikutnya: {{ $statusLabels[$nextStatus] }}.</p></div>
                    </div>
                </div>
                <form method="POST" action="{{ route('findings.update-status', $finding) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="{{ $nextStatus }}">
                    <div class="finding-detail-actions__footer"><button class="button button--primary" type="submit"><i data-lucide="{{ $nextStatus === 'review' ? 'search-check' : 'circle-check-big' }}" aria-hidden="true"></i>{{ $nextStatus === 'review' ? 'Tandai Review' : 'Selesaikan Temuan' }}</button></div>
                </form>
            </section>
        @endif
    </div>

    @if (session('success'))
        <div class="findings-toast is-visible" role="status" aria-live="polite" data-findings-toast><i data-lucide="circle-check" aria-hidden="true"></i><span>{{ session('success') }}</span></div>
    @elseif ($errors->any())
        <div class="findings-toast findings-toast--error is-visible" role="alert" data-findings-toast><i data-lucide="circle-alert" aria-hidden="true"></i><span>{{ $errors->first() }}</span></div>
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/findings-database.js') }}" defer></script>
@endpush
