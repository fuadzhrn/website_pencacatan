@php
    $isMobilePetugas = auth()->user()->role === \App\UserRole::Petugas;
    $isMobileSupervisor = auth()->user()->role === \App\UserRole::Supervisor;
    $canCreateMobileFinding = $isMobilePetugas && $availableDetails->isNotEmpty();
    $mobileListQuery = request()->except(['finding_detail', 'finding_form', 'context']);
    $mobileListUrl = route('mobile.findings', $mobileListQuery);
    $showCreateSheet = $canCreateMobileFinding
        && (request('finding_form') === 'create' || $errors->has('maintenance_check_detail_id') || (old('maintenance_check_detail_id') !== null && ! ($selectedFinding ?? null)));
@endphp

<div class="mobile-findings">
    <header class="mobile-findings-header"><span class="mobile-findings-header__icon"><i data-lucide="triangle-alert"></i></span><div><p>Monitoring Maintenance</p><h1>Temuan</h1><span>Pantau temuan hasil pemeriksaan</span></div></header>

    <section class="mobile-findings-filter"><header><span><i data-lucide="list-filter"></i></span><div><h2>Filter Temuan</h2><p>Temukan data yang perlu ditindaklanjuti.</p></div></header>
        <form method="GET" action="{{ route('mobile.findings') }}">
            <label><span>Mesin</span><select name="machine_id"><option value="">Semua mesin</option>@foreach ($machines as $machine)<option value="{{ $machine->id }}" @selected((string) request('machine_id') === (string) $machine->id)>{{ $machine->equipment_name }}</option>@endforeach</select></label>
            <label><span>Status</span><select name="status"><option value="">Semua status</option>@foreach ($statusLabels as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label><span>Frekuensi</span><select name="frequency"><option value="">Semua frekuensi</option>@foreach ($frequencyLabels as $value => $label)<option value="{{ $value }}" @selected(request('frequency') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label><span>Tanggal</span><input type="date" name="date" value="{{ request('date') }}"></label>
            <label class="mobile-findings-filter__search"><span>Cari temuan</span><span><i data-lucide="search"></i><input type="search" name="search" value="{{ request('search') }}" placeholder="Mesin, item, atau keterangan"></span></label>
            <div class="mobile-findings-filter__actions"><a href="{{ route('mobile.findings') }}">Reset</a><button type="submit">Terapkan</button></div>
        </form>
    </section>

    <section class="mobile-findings-summary" aria-label="Ringkasan temuan">
        @foreach (['total' => 'Total Temuan', 'open' => 'Open', 'review' => 'Review', 'resolved' => 'Resolved'] as $key => $label)<article class="mobile-findings-summary__card mobile-findings-summary__card--{{ $key }}"><span><i data-lucide="circle-alert"></i></span><div><small>{{ $label }}</small><strong>{{ $summary[$key] }}</strong></div></article>@endforeach
    </section>

    <section class="mobile-findings-list-section"><header><div><p>Hasil pemeriksaan</p><h2>Daftar Temuan</h2></div><span>{{ $findings->total() }} temuan</span></header><div class="mobile-findings-list">
        @forelse ($findings as $finding)
            <article class="mobile-finding-card" data-status="{{ $finding->status }}">
                <header class="mobile-finding-card__header"><span class="mobile-finding-card__machine-icon" aria-hidden="true"><i data-lucide="scan-line"></i></span><div><p>{{ $finding->machine->location }}</p><h3>{{ $finding->machine->equipment_name }}</h3></div><span class="mobile-finding-badge mobile-finding-badge--{{ $finding->status }}">{{ $statusLabels[$finding->status] }}</span></header>
                <div class="mobile-finding-card__issue"><span>Item tidak sesuai</span><strong>{{ $finding->item?->item_name ?? '-' }}</strong></div>
                <dl class="mobile-finding-card__details"><div><dt>Keterangan</dt><dd>{{ $finding->finding_description }}</dd></div><div><dt>Tindak lanjut</dt><dd>{{ $finding->follow_up ?: 'Belum ada tindak lanjut.' }}</dd></div></dl>
                <div class="mobile-finding-card__meta">
                    <span><i data-lucide="calendar-days" aria-hidden="true"></i><time datetime="{{ $finding->finding_date->toDateString() }}">{{ $finding->finding_date->translatedFormat('d M Y') }}</time></span>
                    @if ($finding->photo)<div><img src="{{ route('findings.photo', $finding) }}" alt="" loading="lazy"><small>Foto tersedia</small></div>@endif
                </div>
                <footer class="mobile-finding-card__actions">
                    <a href="{{ route('mobile.findings', [...$mobileListQuery, 'finding_detail' => $finding->id]) }}"><i data-lucide="eye" aria-hidden="true"></i>Detail</a>
                    @if ($isMobileSupervisor && $finding->status !== 'resolved')
                        <form method="POST" action="{{ route('findings.update-status', $finding) }}">@csrf @method('PATCH')<input type="hidden" name="context" value="mobile"><input type="hidden" name="status" value="{{ $finding->status === 'open' ? 'review' : 'resolved' }}"><button type="submit"><i data-lucide="refresh-cw" aria-hidden="true"></i>{{ $finding->status === 'open' ? 'Review' : 'Selesaikan' }}</button></form>
                    @elseif ($isMobilePetugas && $finding->reported_by === auth()->id() && $finding->status === 'open' && $finding->reviewed_by === null)
                        <a href="{{ route('mobile.findings', [...$mobileListQuery, 'finding_detail' => $finding->id]) }}"><i data-lucide="pencil" aria-hidden="true"></i>Edit</a>
                    @endif
                </footer>
            </article>
        @empty
            <div class="mobile-findings-empty"><span aria-hidden="true"><i data-lucide="search-x"></i></span><strong>Belum ada temuan</strong><p>Tidak ada data yang sesuai dengan filter.</p></div>
        @endforelse
    </div>@include('partials.pagination', ['paginator' => $findings, 'class' => 'mobile-findings-pagination', 'label' => 'Navigasi halaman temuan'])</section>

    @if ($canCreateMobileFinding)
        <a class="mobile-findings-fab" href="{{ route('mobile.findings', [...$mobileListQuery, 'finding_form' => 'create']) }}"><i data-lucide="plus" aria-hidden="true"></i><span>Tambah Temuan</span></a>
    @endif

    @if ($selectedFinding ?? null)
        @include('mobile.finding-sheet', ['selectedFinding' => $selectedFinding, 'mobileCloseUrl' => $mobileListUrl])
    @elseif ($showCreateSheet)
        <div class="mobile-finding-sheet is-open" aria-hidden="false">
            <a class="mobile-finding-sheet__backdrop" href="{{ $mobileListUrl }}" tabindex="-1" aria-label="Tutup form temuan"></a>
            <section class="mobile-finding-sheet__panel" role="dialog" aria-modal="true" aria-labelledby="mobile-finding-form-title" tabindex="-1">
                <div class="mobile-finding-sheet__handle" aria-hidden="true"></div>
                <header class="mobile-finding-sheet__header">
                    <span aria-hidden="true"><i data-lucide="file-pen-line"></i></span>
                    <div><p>Data temuan pemeriksaan</p><h2 id="mobile-finding-form-title">Tambah Temuan</h2></div>
                    <a href="{{ $mobileListUrl }}" aria-label="Tutup form temuan"><i data-lucide="x" aria-hidden="true"></i></a>
                </header>
                <form class="mobile-finding-form" method="POST" action="{{ route('findings.store') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="context" value="mobile">
                    <input type="hidden" name="status" value="open">
                    <input type="hidden" name="maintenance_check_id" value="{{ old('maintenance_check_id') }}" data-finding-check>
                    <input type="hidden" name="machine_id" value="{{ old('machine_id') }}" data-finding-machine>
                    <input type="hidden" name="maintenance_item_id" value="{{ old('maintenance_item_id') }}" data-finding-item>
                    <label><span>Item tidak sesuai</span><select name="maintenance_check_detail_id" required data-finding-detail-select><option value="">Pilih item pemeriksaan</option>@foreach ($availableDetails as $detail)<option value="{{ $detail->id }}" data-check="{{ $detail->maintenance_check_id }}" data-machine="{{ $detail->check->machine_id }}" data-item="{{ $detail->maintenance_item_id }}" @selected((string) old('maintenance_check_detail_id') === (string) $detail->id)>{{ $detail->check->machine->equipment_name }} — {{ $detail->item->item_name }}</option>@endforeach</select>@error('maintenance_check_detail_id')<span class="form-error">{{ $message }}</span>@enderror</label>
                    <label><span>Keterangan temuan</span><textarea name="finding_description" rows="3" maxlength="5000" placeholder="Jelaskan kondisi yang ditemukan" required>{{ old('finding_description') }}</textarea>@error('finding_description')<span class="form-error">{{ $message }}</span>@enderror</label>
                    <label><span>Tindak lanjut</span><textarea name="follow_up" rows="3" maxlength="5000" placeholder="Jelaskan rencana tindak lanjut">{{ old('follow_up') }}</textarea>@error('follow_up')<span class="form-error">{{ $message }}</span>@enderror</label>
                    <label class="mobile-finding-upload">
                        <span>Foto temuan <small>(opsional, maks. 2 MB)</small></span>
                        <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-mobile-finding-photo-input>
                        <span class="mobile-finding-upload__surface">
                            <span data-mobile-finding-photo-placeholder><i data-lucide="image-up" aria-hidden="true"></i><strong>Pilih foto</strong><small>JPG, PNG, atau WEBP</small></span>
                            <img src="" alt="Preview foto temuan" data-mobile-finding-photo-preview hidden>
                        </span>
                    </label>
                    @error('photo')<p class="mobile-finding-form__error" role="alert">{{ $message }}</p>@enderror
                    <div class="mobile-finding-sheet__footer">
                        <a class="mobile-finding-button mobile-finding-button--soft" href="{{ $mobileListUrl }}">Batal</a>
                        <button type="submit" class="mobile-finding-button mobile-finding-button--primary"><i data-lucide="save" aria-hidden="true"></i>Simpan</button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    @if (session('success') || ($errors->any() && ! $showCreateSheet && ! ($selectedFinding ?? null)))
        <div class="mobile-findings-toast" role="{{ session('success') ? 'status' : 'alert' }}" aria-live="polite" data-mobile-findings-toast>
            <span aria-hidden="true"><i data-lucide="{{ session('success') ? 'circle-check' : 'circle-alert' }}"></i></span>
            <p>{{ session('success') ?? $errors->first() }}</p>
            <button type="button" data-mobile-findings-toast-close aria-label="Tutup notifikasi"><i data-lucide="x" aria-hidden="true"></i></button>
        </div>
    @endif
</div>
