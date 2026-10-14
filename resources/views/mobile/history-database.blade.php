@php
    $mobileChecks = $checks ?? collect(isset($check) ? [$check] : []);
    $mobileSummary = $summary ?? [
        'total' => $mobileChecks->count(),
        'waiting' => $mobileChecks->where('verification_status', 'waiting')->count(),
        'verified' => $mobileChecks->where('verification_status', 'verified')->count(),
        'rejected' => $mobileChecks->where('verification_status', 'rejected')->count(),
    ];
    $mobileIndexRoute = ($isVerificationQueue ?? false)
        ? route('verifications.index')
        : ((request()->routeIs('mobile.history') || request('context') === 'mobile') ? route('mobile.history') : route('inspections.history'));
@endphp
<div class="mobile-history-database">
    <header class="mobile-history-database__header"><span><i data-lucide="{{ ($isVerificationQueue ?? false) ? 'badge-check' : 'history' }}" aria-hidden="true"></i></span><div><h1>{{ ($isVerificationQueue ?? false) ? 'Verifikasi Pemeriksaan' : 'Riwayat Pemeriksaan' }}</h1><p>{{ ($isVerificationQueue ?? false) ? 'Tinjau hasil pemeriksaan petugas' : 'Lihat pemeriksaan yang sudah dilakukan' }}</p></div></header>

    @if (session('success'))<div class="mobile-history-database__alert mobile-history-database__alert--success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="mobile-history-database__alert mobile-history-database__alert--error">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="mobile-history-database__alert mobile-history-database__alert--error" role="alert"><strong>Keputusan belum dapat disimpan.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    @if (isset($check))
        <a class="mobile-verification-detail__back" href="{{ $mobileIndexRoute }}"><i data-lucide="arrow-left" aria-hidden="true"></i>Kembali ke daftar</a>
        <section class="mobile-verification-detail">
            <header><div><small>Mesin X-Ray</small><h2>{{ $check->machine->equipment_name }}</h2><p><i data-lucide="map-pin" aria-hidden="true"></i>{{ $check->machine->location }}</p></div><span class="verification-badge verification-badge--{{ $check->verification_status }}">{{ ($verificationLabels ?? [])[$check->verification_status] ?? $check->verification_status }}</span></header>
            <dl>
                <div><dt>Merk</dt><dd>{{ $check->machine->brand }}</dd></div><div><dt>Tipe</dt><dd>{{ $check->machine->type }}</dd></div>
                <div><dt>Serial Number</dt><dd>{{ $check->machine->serial_number }}</dd></div><div><dt>Tanggal</dt><dd>{{ $check->check_date->translatedFormat('d M Y') }}</dd></div>
                <div><dt>Frekuensi</dt><dd>{{ $check->schedule?->maintenanceCategory?->name ?? '-' }}</dd></div><div><dt>Petugas</dt><dd>{{ $check->checkedBy->name }}</dd></div>
            </dl>
            <div class="mobile-verification-detail__note"><strong>Catatan Pemeriksaan</strong><p>{{ $check->general_note ?: 'Tidak ada catatan.' }}</p></div>
        </section>

        <section class="mobile-verification-detail">
            <h2>Detail Checklist</h2>
            <div class="mobile-verification-detail__items">@forelse ($check->details as $detail)<article><div><strong>{{ $detail->item?->item_name ?? '-' }}</strong><span class="verification-badge verification-badge--{{ $detail->result_status === 'sesuai' ? 'verified' : 'rejected' }}">{{ $detail->result_status === 'sesuai' ? 'Sesuai' : 'Tidak Sesuai' }}</span></div><small>{{ $detail->item?->criteria ?? '-' }}</small><p>{{ $detail->note ?: 'Tanpa catatan.' }}</p></article>@empty<p>Belum ada detail checklist.</p>@endforelse</div>
        </section>

        <section class="mobile-verification-detail">
            <h2>Temuan Pemeriksaan</h2>
            <div class="mobile-verification-detail__items">@forelse ($check->findings as $finding)<article><div><strong>{{ $finding->item?->item_name ?? 'Item pemeriksaan' }}</strong><span class="verification-badge verification-badge--{{ $finding->status === 'resolved' ? 'verified' : 'rejected' }}">{{ ucfirst($finding->status) }}</span></div><p>{{ $finding->finding_description }}</p><small>Tindak lanjut: {{ $finding->follow_up ?: 'Belum ada.' }}</small></article>@empty<p>Tidak ada temuan pada pemeriksaan ini.</p>@endforelse</div>
        </section>

        @if ($check->verification_status === 'waiting' && auth()->user()->role === \App\UserRole::Supervisor)
            <section class="mobile-verification-detail">
                <h2>Keputusan Supervisor</h2><p>Catatan wajib diisi untuk keputusan tolak/revisi.</p>
                <form method="POST" class="mobile-verification-detail__decision">@csrf @method('PATCH')<textarea name="supervisor_note" rows="4" maxlength="5000" placeholder="Catatan hasil tinjauan...">{{ old('supervisor_note') }}</textarea><div><button type="submit" formaction="{{ route('verifications.reject', $check) }}" class="is-reject">Tolak / Revisi</button><button type="submit" formaction="{{ route('verifications.verify', $check) }}">Verifikasi</button></div></form>
            </section>
        @elseif ($check->supervisor_note)
            <section class="mobile-verification-detail"><h2>Catatan Supervisor</h2><p>{{ $check->supervisor_note }}</p></section>
        @endif
    @else

    <form method="GET" action="{{ $mobileIndexRoute }}" class="mobile-history-database__filter">
        <h2><i data-lucide="sliders-horizontal" aria-hidden="true"></i>Filter Riwayat</h2>
        <label><span>Cari mesin/petugas</span><input type="search" name="search" value="{{ request('search') }}"></label>
        <div><label><span>Mulai</span><input type="date" name="start_date" value="{{ request('start_date') }}"></label><label><span>Akhir</span><input type="date" name="end_date" value="{{ request('end_date') }}"></label></div>
        <label><span>Status</span><select name="verification_status"><option value="">Semua status</option>@foreach (($verificationLabels ?? []) as $value => $label)<option value="{{ $value }}" @selected(request('verification_status') === $value)>{{ $label }}</option>@endforeach</select></label>
        <footer><a href="{{ $mobileIndexRoute }}">Reset</a><button type="submit">Terapkan</button></footer>
    </form>

    <section class="mobile-history-database__summary" aria-label="Ringkasan riwayat">
        @foreach (['total' => 'Total', 'waiting' => 'Menunggu', 'verified' => 'Terverifikasi', 'rejected' => 'Ditolak'] as $key => $label)<article><small>{{ $label }}</small><strong>{{ $mobileSummary[$key] }}</strong></article>@endforeach
    </section>

    <section class="mobile-history-database__list">
        @forelse ($mobileChecks as $historyCheck)
            <article class="mobile-history-database__card">
                <header><span><i data-lucide="scan-line" aria-hidden="true"></i></span><div><h2>{{ $historyCheck->machine->equipment_name }}</h2><p><i data-lucide="map-pin" aria-hidden="true"></i>{{ $historyCheck->machine->location }}</p></div></header>
                <dl><div><dt>Tanggal</dt><dd>{{ $historyCheck->check_date->translatedFormat('d M Y') }}</dd></div><div><dt>Frekuensi</dt><dd>{{ $historyCheck->schedule?->maintenanceCategory?->name ?? '-' }}</dd></div><div><dt>Petugas</dt><dd>{{ $historyCheck->checkedBy->name }}</dd></div></dl>
                <div class="mobile-history-database__statuses"><span class="verification-badge verification-badge--{{ $historyCheck->overall_status }}">{{ ($overallLabels ?? [])[$historyCheck->overall_status] ?? $historyCheck->overall_status }}</span><span class="verification-badge verification-badge--{{ $historyCheck->verification_status }}">{{ ($verificationLabels ?? [])[$historyCheck->verification_status] ?? $historyCheck->verification_status }}</span></div>
                <footer>
                    @if (auth()->user()->role === \App\UserRole::Petugas)<a href="{{ route('inspections.show', ['check' => $historyCheck, 'context' => 'mobile']) }}"><i data-lucide="eye" aria-hidden="true"></i>Detail</a>@else<a href="{{ route('verifications.show', $historyCheck) }}"><i data-lucide="eye" aria-hidden="true"></i>Detail</a>@endif
                    <button type="button" onclick="window.print()"><i data-lucide="printer" aria-hidden="true"></i>Cetak</button>
                </footer>
            </article>
        @empty
            <div class="mobile-history-database__empty"><i data-lucide="clipboard-x" aria-hidden="true"></i><h2>Riwayat tidak ditemukan</h2><p>Coba ubah atau reset filter.</p></div>
        @endforelse
    </section>
    @endif
</div>
