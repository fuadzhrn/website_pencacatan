@php
    $mobileChecks = $checks ?? collect(($isHistoryDetail ?? false) ? [$check] : []);
    $mobileSummary = $summary ?? [
        'total' => $mobileChecks->count(),
        'waiting' => $mobileChecks->where('verification_status', 'waiting')->count(),
        'verified' => $mobileChecks->where('verification_status', 'verified')->count(),
        'rejected' => $mobileChecks->where('verification_status', 'rejected')->count(),
    ];
    $mobileIndexRoute = ($isVerificationQueue ?? false)
        ? route('verifications.index')
        : ((request()->routeIs('mobile.history') || request('context') === 'mobile') ? route('mobile.history') : route('inspections.history'));
    $mobilePreviousUrl = url()->previous();
    $mobileBackUrl = str_starts_with($mobilePreviousUrl, $mobileIndexRoute) ? $mobilePreviousUrl : $mobileIndexRoute;
@endphp
<div class="mobile-history-database">
    <header class="mobile-history-database__header"><span><i data-lucide="{{ ($isVerificationQueue ?? false) ? 'badge-check' : 'history' }}" aria-hidden="true"></i></span><div><h1>{{ ($isVerificationQueue ?? false) ? 'Verifikasi Pemeriksaan' : 'Riwayat Pemeriksaan' }}</h1><p>{{ ($isVerificationQueue ?? false) ? 'Tinjau hasil pemeriksaan petugas' : 'Lihat pemeriksaan yang sudah dilakukan' }}</p></div></header>

    @if (session('success'))<div class="mobile-history-database__alert mobile-history-database__alert--success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="mobile-history-database__alert mobile-history-database__alert--error">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="mobile-history-database__alert mobile-history-database__alert--error" role="alert"><strong>{{ ($isHistoryDetail ?? false) ? 'Keputusan belum dapat disimpan.' : 'Filter belum dapat diterapkan.' }}</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    @if ($isHistoryDetail ?? false)
        <a class="mobile-verification-detail__back" href="{{ $mobileBackUrl }}"><i data-lucide="arrow-left" aria-hidden="true"></i>Kembali ke daftar</a>
        <section class="mobile-verification-detail">
            <header><div><small>Mesin X-Ray</small><h2>{{ $check->machine->equipment_name }}</h2><p><i data-lucide="map-pin" aria-hidden="true"></i>{{ $check->machine->location }}</p></div><span class="verification-badge verification-badge--{{ $check->verification_status }}">{{ ($verificationLabels ?? [])[$check->verification_status] ?? $check->verification_status }}</span></header>
            <dl>
                <div><dt>Merk</dt><dd>{{ $check->machine->brand }}</dd></div><div><dt>Tipe</dt><dd>{{ $check->machine->type }}</dd></div>
                <div><dt>Serial Number</dt><dd>{{ $check->machine->serial_number }}</dd></div><div><dt>Tanggal</dt><dd>{{ $check->check_date->translatedFormat('d M Y') }}</dd></div>
                <div><dt>Kategori / Frekuensi</dt><dd>{{ $check->schedule?->maintenanceCategory?->name ?? '-' }} · {{ $frequencyLabels[$check->schedule?->maintenanceCategory?->frequency] ?? ucfirst($check->schedule?->maintenanceCategory?->frequency ?? '-') }}</dd></div><div><dt>Petugas</dt><dd>{{ $check->checkedBy->name }}</dd></div>
                <div><dt>Jadwal</dt><dd>{{ $check->schedule?->scheduled_date?->translatedFormat('d M Y') ?? '-' }} {{ $check->schedule?->scheduled_time ?? '' }}</dd></div><div><dt>Waktu</dt><dd>{{ $check->start_time }} - {{ $check->end_time }}</dd></div>
                <div><dt>Hasil</dt><dd>{{ ($overallLabels ?? [])[$check->overall_status] ?? $check->overall_status }}</dd></div><div><dt>Verifikasi</dt><dd>{{ ($verificationLabels ?? [])[$check->verification_status] ?? $check->verification_status }}</dd></div>
            </dl>
            <div class="mobile-verification-detail__note"><strong>Catatan Pemeriksaan</strong><p>{{ $check->general_note ?: 'Tidak ada catatan.' }}</p></div>
        </section>

        <section class="mobile-verification-detail">
            <h2>Detail Checklist</h2>
            <div class="mobile-verification-detail__items">@forelse ($check->details as $detail)<article><div><strong>{{ $detail->item?->item_name ?? '-' }}</strong><span class="verification-badge verification-badge--{{ match ($detail->result_status) { 'sesuai' => 'verified', 'tidak_sesuai' => 'rejected', default => 'waiting' } }}">{{ ($itemResultLabels ?? [])[$detail->result_status] ?? $detail->result_status }}</span></div><small>Kategori: {{ $detail->item?->category?->name ?? '-' }}</small><small>Kriteria: {{ $detail->item?->criteria ?? '-' }}</small><p>Nilai aktual: {{ $detail->actual_value ?: '-' }}</p><p>Catatan: {{ $detail->note ?: '-' }}</p>@if ($detail->photo)<a class="verification-photo-link" href="{{ route('inspections.history.details.photo', [$check, $detail]) }}" target="_blank" rel="noopener">Lihat foto checklist</a>@endif</article>@empty<p>Belum ada detail checklist.</p>@endforelse</div>
        </section>

        <section class="mobile-verification-detail">
            <h2>Temuan Pemeriksaan</h2>
            <div class="mobile-verification-detail__items">@forelse ($check->findings as $finding)<article><div><strong>{{ $finding->item?->item_name ?? 'Item pemeriksaan' }}</strong><span class="verification-badge verification-badge--{{ $finding->status === 'resolved' ? 'verified' : ($finding->status === 'review' ? 'waiting' : 'rejected') }}">{{ ($findingStatusLabels ?? [])[$finding->status] ?? $finding->status }}</span></div><p>{{ $finding->finding_description }}</p><small>Tindak lanjut: {{ $finding->follow_up ?: 'Belum ada.' }}</small><small>Pelapor: {{ $finding->reportedBy?->name ?? '-' }} · {{ $finding->finding_date?->translatedFormat('d M Y') ?? '-' }}</small>@if ($finding->photo)<a class="verification-photo-link" href="{{ route('inspections.history.findings.photo', [$check, $finding]) }}" target="_blank" rel="noopener">Lihat foto temuan</a>@endif</article>@empty<p>Tidak ada temuan pada pemeriksaan ini.</p>@endforelse</div>
        </section>

        @if (($isVerificationQueue ?? false) && $check->verification_status === 'waiting' && auth()->user()->role === \App\UserRole::Supervisor)
            <section class="mobile-verification-detail">
                <h2>Keputusan Supervisor</h2><p>Catatan wajib diisi untuk keputusan tolak/revisi.</p>
                <form method="POST" class="mobile-verification-detail__decision">@csrf @method('PATCH')<textarea name="supervisor_note" rows="4" maxlength="5000" placeholder="Catatan hasil tinjauan...">{{ old('supervisor_note') }}</textarea>@error('supervisor_note')<span class="form-error">{{ $message }}</span>@enderror<div><button type="submit" formaction="{{ route('verifications.reject', $check) }}" class="is-reject">Tolak / Revisi</button><button type="submit" formaction="{{ route('verifications.verify', $check) }}">Verifikasi</button></div></form>
            </section>
        @else
            <section class="mobile-verification-detail"><h2>Audit Verifikasi</h2><p>{{ $check->supervisor_note ?: 'Belum ada catatan supervisor.' }}</p>@if ($check->verifiedBy)<small>Diproses oleh {{ $check->verifiedBy->name }} pada {{ $check->verified_at?->translatedFormat('d M Y H:i') }}</small>@else<small>Pemeriksaan masih menunggu verifikasi supervisor.</small>@endif @if (! ($isVerificationQueue ?? false) && $check->verification_status === 'waiting' && auth()->user()->role === \App\UserRole::Supervisor)<a class="verification-button verification-button--primary" href="{{ route('verifications.show', $check) }}">Buka Proses Verifikasi</a>@endif</section>
        @endif
    @else

    <form method="GET" action="{{ $mobileIndexRoute }}" class="mobile-history-database__filter">
        <h2><i data-lucide="sliders-horizontal" aria-hidden="true"></i>Filter Riwayat</h2>
        <label><span>Cari nama mesin/serial</span><input type="search" name="search" value="{{ request('search') }}"></label>
        <div><label><span>Mulai</span><input type="date" name="start_date" value="{{ request('start_date') }}"></label><label><span>Akhir</span><input type="date" name="end_date" value="{{ request('end_date') }}"></label></div>
        <label><span>Mesin</span><select name="machine_id"><option value="">Semua mesin</option>@foreach (($machines ?? []) as $machine)<option value="{{ $machine->id }}" @selected((string) request('machine_id') === (string) $machine->id)>{{ $machine->equipment_name }}</option>@endforeach</select></label>
        <label><span>Petugas</span><select name="officer_id"><option value="">Semua petugas</option>@foreach (($officers ?? []) as $officer)<option value="{{ $officer->id }}" @selected((string) request('officer_id') === (string) $officer->id)>{{ $officer->name }}</option>@endforeach</select></label>
        <label><span>Kategori</span><select name="category_id"><option value="">Semua kategori</option>@foreach (($categories ?? []) as $category)<option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>@endforeach</select></label>
        <label><span>Status</span><select name="verification_status"><option value="">Semua status</option>@foreach (($verificationLabels ?? []) as $value => $label)<option value="{{ $value }}" @selected(request('verification_status') === $value)>{{ $label }}</option>@endforeach</select></label>
        <label><span>Hasil</span><select name="overall_status"><option value="">Semua hasil</option>@foreach (($overallLabels ?? []) as $value => $label)<option value="{{ $value }}" @selected(request('overall_status') === $value)>{{ $label }}</option>@endforeach</select></label>
        <footer><a href="{{ $mobileIndexRoute }}">Reset</a><button type="submit">Terapkan</button></footer>
    </form>

    <section class="mobile-history-database__summary" aria-label="Ringkasan riwayat">
        @foreach (['total' => 'Total', 'waiting' => 'Menunggu', 'verified' => 'Terverifikasi', 'rejected' => 'Ditolak/Revisi'] as $key => $label)<article><small>{{ $label }}</small><strong>{{ $mobileSummary[$key] }}</strong></article>@endforeach
    </section>

    <section class="mobile-history-database__list">
        @forelse ($mobileChecks as $historyCheck)
            <article class="mobile-history-database__card">
                <header><span><i data-lucide="scan-line" aria-hidden="true"></i></span><div><h2>{{ $historyCheck->machine->equipment_name }}</h2><p><i data-lucide="map-pin" aria-hidden="true"></i>{{ $historyCheck->machine->location }}</p></div></header>
                <dl><div><dt>Tanggal</dt><dd>{{ $historyCheck->check_date->translatedFormat('d M Y') }}</dd></div><div><dt>Kategori / Frekuensi</dt><dd>{{ $historyCheck->schedule?->maintenanceCategory?->name ?? '-' }} · {{ $frequencyLabels[$historyCheck->schedule?->maintenanceCategory?->frequency] ?? ucfirst($historyCheck->schedule?->maintenanceCategory?->frequency ?? '-') }}</dd></div><div><dt>Petugas</dt><dd>{{ $historyCheck->checkedBy->name }}</dd></div></dl>
                <div class="mobile-history-database__statuses"><span class="verification-badge verification-badge--{{ $historyCheck->overall_status }}">{{ ($overallLabels ?? [])[$historyCheck->overall_status] ?? $historyCheck->overall_status }}</span><span class="verification-badge verification-badge--{{ $historyCheck->verification_status }}">{{ ($verificationLabels ?? [])[$historyCheck->verification_status] ?? $historyCheck->verification_status }}</span></div>
                <footer>
                    @if ($isVerificationQueue ?? false)<a href="{{ route('verifications.show', ['check' => $historyCheck, 'context' => 'mobile']) }}"><i data-lucide="eye" aria-hidden="true"></i>Detail</a>@else<a href="{{ route('inspections.history.show', ['check' => $historyCheck, 'context' => 'mobile']) }}"><i data-lucide="eye" aria-hidden="true"></i>Detail</a>@endif
                    <button type="button" onclick="window.print()"><i data-lucide="printer" aria-hidden="true"></i>Cetak</button>
                </footer>
            </article>
        @empty
            <div class="mobile-history-database__empty"><i data-lucide="clipboard-x" aria-hidden="true"></i><h2>Riwayat tidak ditemukan</h2><p>Coba ubah atau reset filter.</p></div>
        @endforelse
    </section>
    @if ($mobileChecks instanceof \Illuminate\Pagination\LengthAwarePaginator && $mobileChecks->hasPages())
        <nav class="mobile-history-database__pagination" aria-label="Navigasi halaman riwayat">
            @if ($mobileChecks->onFirstPage())<span aria-disabled="true">Sebelumnya</span>@else<a href="{{ $mobileChecks->previousPageUrl() }}" rel="prev">Sebelumnya</a>@endif
            <small>{{ $mobileChecks->currentPage() }} / {{ $mobileChecks->lastPage() }}</small>
            @if ($mobileChecks->hasMorePages())<a href="{{ $mobileChecks->nextPageUrl() }}" rel="next">Berikutnya</a>@else<span aria-disabled="true">Berikutnya</span>@endif
        </nav>
    @endif
    @endif
</div>
