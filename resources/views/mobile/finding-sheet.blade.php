@php
    $sheetItemName = $selectedFinding->item?->item_name ?? 'Item pemeriksaan';
    $canEditSelectedFinding = auth()->user()->role === \App\UserRole::Petugas
        && $selectedFinding->reported_by === auth()->id()
        && $selectedFinding->status === 'open'
        && $selectedFinding->reviewed_by === null;
    $canChangeSelectedStatus = auth()->user()->role === \App\UserRole::Supervisor && $selectedFinding->status !== 'resolved';
    $selectedNextStatus = $selectedFinding->status === 'open' ? 'review' : 'resolved';
@endphp

<div class="mobile-finding-sheet is-open" aria-hidden="false">
    <a class="mobile-finding-sheet__backdrop" href="{{ $mobileCloseUrl }}" tabindex="-1" aria-label="Tutup detail temuan"></a>
    <section class="mobile-finding-sheet__panel" role="dialog" aria-modal="true" aria-labelledby="mobile-finding-detail-title" tabindex="-1">
        <div class="mobile-finding-sheet__handle" aria-hidden="true"></div>
        <header class="mobile-finding-sheet__header">
            <span aria-hidden="true"><i data-lucide="clipboard-search"></i></span>
            <div><p>Informasi pemeriksaan</p><h2 id="mobile-finding-detail-title">Detail Temuan</h2></div>
            <a href="{{ $mobileCloseUrl }}" aria-label="Tutup detail"><i data-lucide="x" aria-hidden="true"></i></a>
        </header>

        <div class="mobile-finding-detail">
            <div class="mobile-finding-detail__hero">
                <div><small>{{ $selectedFinding->machine->location }}</small><h3>{{ $sheetItemName }}</h3><p>{{ $selectedFinding->machine->equipment_name }}</p></div>
                <span class="mobile-finding-badge mobile-finding-badge--{{ $selectedFinding->status }}">{{ $statusLabels[$selectedFinding->status] ?? $selectedFinding->status }}</span>
            </div>

            <dl class="mobile-finding-detail__grid">
                <div><dt>Tanggal temuan</dt><dd>{{ $selectedFinding->finding_date->translatedFormat('d M Y') }}</dd></div>
                <div><dt>Petugas</dt><dd>{{ $selectedFinding->reportedBy->name }}</dd></div>
                <div><dt>Frekuensi</dt><dd>{{ $frequencyLabels[$selectedFinding->item?->category?->frequency] ?? '-' }}</dd></div>
                <div><dt>Ditinjau oleh</dt><dd>{{ $selectedFinding->reviewedBy?->name ?? '-' }}</dd></div>
                <div class="mobile-finding-detail__wide"><dt>Kriteria pemeriksaan</dt><dd>{{ $selectedFinding->item?->criteria ?? '-' }}</dd></div>
                <div class="mobile-finding-detail__wide"><dt>Keterangan temuan</dt><dd>{{ $selectedFinding->finding_description }}</dd></div>
                <div class="mobile-finding-detail__wide"><dt>Tindak lanjut</dt><dd>{{ $selectedFinding->follow_up ?: 'Belum ada tindak lanjut.' }}</dd></div>
            </dl>

            @if ($selectedFinding->photo)
                <a class="mobile-finding-detail__photo" href="{{ route('findings.photo', $selectedFinding) }}" target="_blank" rel="noopener">
                    <img src="{{ route('findings.photo', $selectedFinding) }}" alt="Foto dokumentasi temuan {{ $sheetItemName }}" loading="lazy">
                    <small>Ketuk untuk membuka foto ukuran penuh</small>
                </a>
            @else
                <div class="mobile-finding-detail__photo-empty"><i data-lucide="image-off" aria-hidden="true"></i><span>Foto temuan tidak tersedia.</span></div>
            @endif
        </div>

        @if ($canEditSelectedFinding)
            <form class="mobile-finding-form" method="POST" action="{{ route('findings.update', $selectedFinding) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="context" value="mobile">
                <input type="hidden" name="maintenance_check_id" value="{{ $selectedFinding->maintenance_check_id }}">
                <input type="hidden" name="maintenance_check_detail_id" value="{{ $selectedFinding->maintenance_check_detail_id }}">
                <input type="hidden" name="machine_id" value="{{ $selectedFinding->machine_id }}">
                <input type="hidden" name="maintenance_item_id" value="{{ $selectedFinding->maintenance_item_id }}">
                <input type="hidden" name="status" value="open">
                <label><span>Keterangan temuan</span><textarea name="finding_description" rows="3" maxlength="5000" required>{{ old('finding_description', $selectedFinding->finding_description) }}</textarea>@error('finding_description')<span class="form-error">{{ $message }}</span>@enderror</label>
                <label><span>Tindak lanjut</span><textarea name="follow_up" rows="3" maxlength="5000">{{ old('follow_up', $selectedFinding->follow_up) }}</textarea>@error('follow_up')<span class="form-error">{{ $message }}</span>@enderror</label>
                <label class="mobile-finding-upload">
                    <span>Ganti foto <small>(opsional, maks. 2 MB)</small></span>
                    <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-mobile-finding-photo-input>
                    <span class="mobile-finding-upload__surface">
                        <span data-mobile-finding-photo-placeholder><i data-lucide="image-up" aria-hidden="true"></i><strong>Pilih foto</strong><small>JPG, PNG, atau WEBP</small></span>
                        <img src="" alt="Preview foto temuan" data-mobile-finding-photo-preview hidden>
                    </span>
                </label>
                @error('photo')<p class="mobile-finding-form__error" role="alert">{{ $message }}</p>@enderror
                <div class="mobile-finding-sheet__footer">
                    <a class="mobile-finding-button mobile-finding-button--soft" href="{{ $mobileCloseUrl }}">Tutup</a>
                    <button type="submit" class="mobile-finding-button mobile-finding-button--primary"><i data-lucide="save" aria-hidden="true"></i>Perbarui</button>
                </div>
            </form>
        @elseif ($canChangeSelectedStatus)
            <form class="mobile-finding-status-form" method="POST" action="{{ route('findings.update-status', $selectedFinding) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="context" value="mobile">
                <input type="hidden" name="status" value="{{ $selectedNextStatus }}">
                <p>Status saat ini {{ $statusLabels[$selectedFinding->status] }}. Tahap berikutnya: {{ $statusLabels[$selectedNextStatus] }}.</p>
                <div class="mobile-finding-sheet__footer">
                    <a class="mobile-finding-button mobile-finding-button--soft" href="{{ $mobileCloseUrl }}">Tutup</a>
                    <button type="submit" class="mobile-finding-button mobile-finding-button--primary"><i data-lucide="refresh-cw" aria-hidden="true"></i>{{ $selectedNextStatus === 'review' ? 'Tandai Review' : 'Selesaikan' }}</button>
                </div>
            </form>
        @else
            <footer class="mobile-finding-sheet__footer">
                <a class="mobile-finding-button mobile-finding-button--primary mobile-finding-sheet__single-action" href="{{ $mobileCloseUrl }}">Tutup</a>
            </footer>
        @endif
    </section>
</div>
