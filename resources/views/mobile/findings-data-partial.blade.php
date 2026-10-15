<div class="mobile-findings">
    <header class="mobile-findings-header"><span class="mobile-findings-header__icon"><i data-lucide="triangle-alert"></i></span><div><p>Monitoring Maintenance</p><h1>Temuan</h1><span>Pantau temuan hasil pemeriksaan</span></div></header>
    @if (session('success'))<div class="mobile-findings-toast" role="status"><p>{{ session('success') }}</p></div>@endif
    @if ($errors->any())<div class="mobile-findings-toast" role="alert"><p>{{ $errors->first() }}</p></div>@endif

    <section class="mobile-findings-filter"><header><span><i data-lucide="list-filter"></i></span><div><h2>Filter Temuan</h2><p>Temukan data yang perlu ditindaklanjuti.</p></div></header>
        <form method="GET" action="{{ route('mobile.findings') }}">
            <label><span>Mesin</span><select name="machine_id"><option value="">Semua mesin</option>@foreach ($machines as $machine)<option value="{{ $machine->id }}" @selected((string) request('machine_id') === (string) $machine->id)>{{ $machine->equipment_name }}</option>@endforeach</select></label>
            <label><span>Status</span><select name="status"><option value="">Semua status</option>@foreach ($statusLabels as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label><span>Tanggal</span><input type="date" name="date" value="{{ request('date') }}"></label>
            <label class="mobile-findings-filter__search"><span>Cari temuan</span><span><i data-lucide="search"></i><input type="search" name="search" value="{{ request('search') }}" placeholder="Item atau tindak lanjut"></span></label>
            <div class="mobile-findings-filter__actions"><a href="{{ route('mobile.findings') }}">Reset</a><button type="submit">Terapkan</button></div>
        </form>
    </section>

    <section class="mobile-findings-summary" aria-label="Ringkasan temuan">
        @foreach (['total' => 'Total Temuan', 'open' => 'Open', 'review' => 'Review', 'resolved' => 'Resolved'] as $key => $label)<article class="mobile-findings-summary__card mobile-findings-summary__card--{{ $key }}"><span><i data-lucide="circle-alert"></i></span><div><small>{{ $label }}</small><strong>{{ $summary[$key] }}</strong></div></article>@endforeach
    </section>

    <section class="mobile-findings-list-section"><header><div><p>Hasil pemeriksaan</p><h2>Daftar Temuan</h2></div><span>{{ $findings->total() }} temuan</span></header><div class="mobile-findings-list">
        @forelse ($findings as $finding)
            <article class="mobile-finding-card"><header class="mobile-finding-card__header"><span class="mobile-finding-card__machine-icon"><i data-lucide="scan-line"></i></span><div><p>{{ $finding->machine->location }}</p><h3>{{ $finding->machine->equipment_name }}</h3></div><span class="mobile-finding-badge mobile-finding-badge--{{ $finding->status }}">{{ $statusLabels[$finding->status] }}</span></header>
                <div class="mobile-finding-card__issue"><span>Item tidak sesuai</span><strong>{{ $finding->item?->item_name ?? '-' }}</strong></div><dl class="mobile-finding-card__details"><div><dt>Keterangan</dt><dd>{{ $finding->finding_description }}</dd></div><div><dt>Tindak lanjut</dt><dd>{{ $finding->follow_up ?: 'Belum ada tindak lanjut.' }}</dd></div></dl>
                @if ($finding->photo)<div class="mobile-finding-card__meta"><img src="{{ route('findings.photo', $finding) }}" alt="Foto temuan" loading="lazy"><small>Foto tersedia</small></div>@endif
                <footer class="mobile-finding-card__actions"><a href="{{ route('findings.show', $finding) }}"><i data-lucide="eye"></i>Detail</a>@if (auth()->user()->role === \App\UserRole::Supervisor && $finding->status !== 'resolved')<form method="POST" action="{{ route('findings.update-status', $finding) }}">@csrf @method('PATCH')<input type="hidden" name="context" value="mobile"><input type="hidden" name="status" value="{{ $finding->status === 'open' ? 'review' : 'resolved' }}"><button type="submit">{{ $finding->status === 'open' ? 'Review' : 'Selesaikan' }}</button></form>@endif</footer>
            </article>
        @empty<div class="mobile-findings-empty"><strong>Belum ada temuan</strong><p>Tidak ada data yang sesuai dengan filter.</p></div>@endforelse
    </div>@include('partials.pagination', ['paginator' => $findings, 'class' => 'mobile-findings-pagination', 'label' => 'Navigasi halaman temuan'])</section>

    @if (auth()->user()->role === \App\UserRole::Petugas && $availableDetails->isNotEmpty())
        <details><summary class="mobile-findings-fab"><i data-lucide="plus"></i><span>Tambah Temuan</span></summary><section class="mobile-finding-sheet__panel"><form method="POST" action="{{ route('findings.store') }}" enctype="multipart/form-data" class="mobile-finding-form">@csrf<input type="hidden" name="context" value="mobile"><input type="hidden" name="status" value="open"><label><span>Item tidak sesuai</span><select name="maintenance_check_detail_id" required data-finding-detail-select><option value="">Pilih item</option>@foreach ($availableDetails as $detail)<option value="{{ $detail->id }}" data-check="{{ $detail->maintenance_check_id }}" data-machine="{{ $detail->check->machine_id }}" data-item="{{ $detail->maintenance_item_id }}">{{ $detail->check->machine->equipment_name }} — {{ $detail->item->item_name }}</option>@endforeach</select></label><input type="hidden" name="maintenance_check_id" data-finding-check><input type="hidden" name="machine_id" data-finding-machine><input type="hidden" name="maintenance_item_id" data-finding-item><label><span>Keterangan</span><textarea name="finding_description" rows="3" required></textarea></label><label><span>Tindak lanjut</span><textarea name="follow_up" rows="3"></textarea></label><label><span>Foto</span><input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label><button type="submit" class="mobile-finding-button mobile-finding-button--primary">Simpan</button></form></section></details>
    @endif
</div>
