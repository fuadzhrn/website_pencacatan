@extends('layouts.app')

@section('title', 'Temuan Pemeriksaan')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/findings.css') }}">
@endpush

@section('content')
    @php
        $isPetugas = auth()->user()->role === \App\UserRole::Petugas;
        $isSupervisor = auth()->user()->role === \App\UserRole::Supervisor;
        $canCreateFinding = $isPetugas && $availableDetails->isNotEmpty();
        $hasCreateErrors = $errors->hasAny(['maintenance_check_detail_id', 'maintenance_check_id', 'machine_id', 'maintenance_item_id', 'finding_description', 'follow_up', 'photo']);
        $selectedDate = request('date') ? \Illuminate\Support\Carbon::parse(request('date')) : null;
    @endphp

    <div class="findings-page" data-findings-page @if ($canCreateFinding && $hasCreateErrors) data-open-dialog="finding-form-dialog" @endif>
        <header class="findings-page-header">
            <div>
                <p class="findings-page-header__eyebrow">Monitoring Maintenance</p>
                <h2>Temuan Pemeriksaan</h2>
                <p>Temuan berasal dari hasil pemeriksaan yang tidak sesuai.</p>
            </div>
            @if ($canCreateFinding)
                <button class="button button--primary" type="button" data-dialog-open="finding-form-dialog">
                    <i data-lucide="plus" aria-hidden="true"></i>
                    Tambah Temuan
                </button>
            @endif
        </header>

        <section class="findings-summary" aria-label="Ringkasan temuan pemeriksaan">
            @foreach ([
                ['total', 'Total Temuan', 'clipboard-list', 'Seluruh temuan tercatat'],
                ['open', 'Open', 'circle-alert', 'Perlu tindak lanjut'],
                ['review', 'Review', 'search-check', 'Dalam peninjauan'],
                ['resolved', 'Resolved', 'circle-check-big', 'Sudah diselesaikan'],
            ] as [$key, $label, $icon, $description])
                <article class="findings-summary-card findings-summary-card--{{ $key }}">
                    <span class="findings-summary-card__icon" aria-hidden="true"><i data-lucide="{{ $icon }}"></i></span>
                    <div><p>{{ $label }}</p><strong>{{ $summary[$key] }}</strong><span>{{ $description }}</span></div>
                </article>
            @endforeach
        </section>

        <section class="findings-filter-panel" aria-labelledby="findings-filter-title">
            <div class="findings-section-heading">
                <div>
                    <span class="findings-section-heading__icon" aria-hidden="true"><i data-lucide="list-filter"></i></span>
                    <div><h3 id="findings-filter-title">Filter Temuan</h3><p>Temukan data berdasarkan tanggal, mesin, status, frekuensi, atau kata kunci.</p></div>
                </div>
            </div>
            <form class="findings-filter" method="GET" action="{{ route('findings.index') }}">
                <label class="field"><span>Tanggal</span><input type="date" name="date" value="{{ request('date') }}"></label>
                <label class="field"><span>Mesin</span><select name="machine_id"><option value="">Semua Mesin</option>@foreach ($machines as $machine)<option value="{{ $machine->id }}" @selected((string) request('machine_id') === (string) $machine->id)>{{ $machine->equipment_name }}</option>@endforeach</select></label>
                <label class="field"><span>Status</span><select name="status"><option value="">Semua Status</option>@foreach ($statusLabels as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></label>
                <label class="field"><span>Frekuensi</span><select name="frequency"><option value="">Semua Frekuensi</option>@foreach ($frequencyLabels as $value => $label)<option value="{{ $value }}" @selected(request('frequency') === $value)>{{ $label }}</option>@endforeach</select></label>
                <label class="field findings-filter__search"><span>Pencarian</span><span class="input-with-icon"><i data-lucide="search" aria-hidden="true"></i><input type="search" name="search" value="{{ request('search') }}" placeholder="Cari mesin, item, atau keterangan..."></span></label>
                <div class="findings-filter__actions">
                    <button class="button button--primary" type="submit"><i data-lucide="filter" aria-hidden="true"></i>Terapkan</button>
                    <a class="button button--secondary" href="{{ route('findings.index') }}"><i data-lucide="rotate-ccw" aria-hidden="true"></i>Reset</a>
                </div>
            </form>
        </section>

        <section class="findings-table-panel" aria-labelledby="findings-table-title">
            <div class="findings-table-panel__header">
                <div>
                    <h3 id="findings-table-title">Daftar Temuan</h3>
                    <p>{{ $findings->count() }} dari {{ $findings->total() }} data ditampilkan</p>
                </div>
                <span class="findings-table-panel__period"><i data-lucide="calendar-days" aria-hidden="true"></i>{{ $selectedDate ? $selectedDate->translatedFormat('d F Y') : 'Semua periode' }}</span>
            </div>
            <div class="findings-table-scroll">
                <table class="findings-table">
                    <thead><tr><th>No</th><th>Tanggal</th><th>Mesin</th><th>Lokasi</th><th>Item Tidak Sesuai</th><th>Keterangan Temuan</th><th>Tindak Lanjut</th><th>Status</th><th>Foto</th><th>Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($findings as $finding)
                            @php
                                $itemName = $finding->item?->item_name ?? 'Item pemeriksaan';
                                $frequency = $finding->item?->category?->frequency;
                                $canEditFinding = $isPetugas && $finding->reported_by === auth()->id() && $finding->status === 'open' && $finding->reviewed_by === null;
                            @endphp
                            <tr>
                                <td class="findings-table__number">{{ $findings->firstItem() + $loop->index }}</td>
                                <td><time datetime="{{ $finding->finding_date->toDateString() }}">{{ $finding->finding_date->translatedFormat('d M Y') }}</time><small>{{ $frequencyLabels[$frequency] ?? '-' }}</small></td>
                                <td><strong>{{ $finding->machine->equipment_name }}</strong><small>{{ $finding->reportedBy->name }}</small></td>
                                <td>{{ $finding->machine->location }}</td>
                                <td><strong>{{ $itemName }}</strong></td>
                                <td class="findings-table__description">{{ $finding->finding_description }}</td>
                                <td class="findings-table__description">{{ $finding->follow_up ?: 'Belum ada tindak lanjut.' }}</td>
                                <td><span class="status-badge status-badge--{{ $finding->status }}"><span aria-hidden="true"></span>{{ $statusLabels[$finding->status] ?? $finding->status }}</span></td>
                                <td>
                                    @if ($finding->photo)
                                        <button class="finding-photo-button" type="button" data-photo-preview data-photo-url="{{ route('findings.photo', $finding) }}" data-photo-label="{{ $itemName }} — {{ $finding->machine->equipment_name }}" aria-label="Lihat foto temuan {{ $itemName }}">
                                            <span class="finding-photo" aria-hidden="true"><img src="{{ route('findings.photo', $finding) }}" alt="" loading="lazy"></span>
                                            <small>Lihat foto</small>
                                        </button>
                                    @else
                                        <span class="finding-photo-empty"><i data-lucide="image-off" aria-hidden="true"></i>Tidak ada foto</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a class="icon-button" href="{{ route('findings.show', $finding) }}" title="Lihat detail" aria-label="Lihat detail temuan {{ $itemName }}"><i data-lucide="eye" aria-hidden="true"></i></a>
                                        @if ($canEditFinding)
                                            <a class="icon-button" href="{{ route('findings.show', $finding) }}#finding-edit" title="Edit temuan" aria-label="Edit temuan {{ $itemName }}"><i data-lucide="pencil" aria-hidden="true"></i></a>
                                        @endif
                                        @if ($isSupervisor && $finding->status !== 'resolved')
                                            <button type="button" class="icon-button icon-button--status" data-finding-status data-action="{{ route('findings.update-status', $finding) }}" data-current-status="{{ $finding->status }}" data-label="{{ $itemName }} — {{ $finding->machine->equipment_name }}" title="Ubah status" aria-label="Ubah status temuan {{ $itemName }}"><i data-lucide="refresh-cw" aria-hidden="true"></i></button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr class="findings-table__empty"><td colspan="10"><i data-lucide="search-x" aria-hidden="true"></i><strong>Belum ada temuan</strong><span>Tidak ada data yang sesuai dengan filter.</span></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <footer class="findings-table-panel__footer">
                <p>Menampilkan <strong>{{ $findings->firstItem() ?? 0 }}&ndash;{{ $findings->lastItem() ?? 0 }}</strong> dari {{ $findings->total() }} data temuan</p>
                @include('partials.pagination', ['paginator' => $findings, 'class' => 'findings-pagination', 'label' => 'Navigasi halaman temuan'])
            </footer>
        </section>
    </div>

    @if ($canCreateFinding)
        <dialog class="findings-dialog findings-dialog--form" id="finding-form-dialog" aria-labelledby="finding-form-title">
            <form class="findings-dialog__surface" method="POST" action="{{ route('findings.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="status" value="open">
                <input type="hidden" name="maintenance_check_id" value="{{ old('maintenance_check_id') }}" data-finding-check>
                <input type="hidden" name="machine_id" value="{{ old('machine_id') }}" data-finding-machine>
                <input type="hidden" name="maintenance_item_id" value="{{ old('maintenance_item_id') }}" data-finding-item>
                <header class="findings-dialog__header"><div><span class="findings-dialog__icon" aria-hidden="true"><i data-lucide="file-pen-line"></i></span><div><h3 id="finding-form-title">Tambah Temuan</h3><p>Catat temuan untuk item pemeriksaan yang tidak sesuai.</p></div></div><button type="button" class="dialog-close" data-dialog-close aria-label="Tutup dialog"><i data-lucide="x" aria-hidden="true"></i></button></header>
                <div class="findings-dialog__body findings-form-grid">
                    <label class="field findings-form-grid__wide"><span>Item Tidak Sesuai</span>
                        <select name="maintenance_check_detail_id" required data-finding-detail-select>
                            <option value="">Pilih item pemeriksaan</option>
                            @foreach ($availableDetails as $detail)
                                <option value="{{ $detail->id }}" data-check="{{ $detail->maintenance_check_id }}" data-machine="{{ $detail->check->machine_id }}" data-item="{{ $detail->maintenance_item_id }}" @selected((string) old('maintenance_check_detail_id') === (string) $detail->id)>{{ $detail->check->machine->equipment_name }} — {{ $detail->item->item_name }} ({{ $detail->check->check_date->translatedFormat('d M Y') }})</option>
                            @endforeach
                        </select>
                        @error('maintenance_check_detail_id')<span class="form-error">{{ $message }}</span>@enderror
                    </label>
                    <label class="field findings-form-grid__wide"><span>Keterangan Temuan</span><textarea name="finding_description" rows="3" maxlength="5000" placeholder="Jelaskan kondisi yang ditemukan" required>{{ old('finding_description') }}</textarea>@error('finding_description')<span class="form-error">{{ $message }}</span>@enderror</label>
                    <label class="field findings-form-grid__wide"><span>Tindak Lanjut <small>(opsional)</small></span><textarea name="follow_up" rows="3" maxlength="5000" placeholder="Jelaskan tindak lanjut yang direncanakan">{{ old('follow_up') }}</textarea>@error('follow_up')<span class="form-error">{{ $message }}</span>@enderror</label>
                    <label class="field findings-form-grid__wide"><span>Foto Temuan <small>(opsional, maks. 2 MB)</small></span><span class="finding-upload"><input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-photo-input><span class="finding-upload__placeholder" data-upload-placeholder><i data-lucide="image-up" aria-hidden="true"></i><strong>Pilih foto dokumentasi</strong><small>JPG, PNG, atau WEBP</small></span><img src="" alt="Preview foto temuan" data-upload-preview hidden></span>@error('photo')<span class="form-error">{{ $message }}</span>@enderror</label>
                </div>
                <footer class="findings-dialog__footer"><button class="button button--secondary" type="button" data-dialog-close>Batal</button><button class="button button--primary" type="submit"><i data-lucide="save" aria-hidden="true"></i>Simpan Temuan</button></footer>
            </form>
        </dialog>
    @endif

    @if ($isSupervisor)
        <dialog class="findings-dialog findings-dialog--status" id="finding-status-dialog" aria-labelledby="finding-status-title">
            <form class="findings-dialog__surface" method="POST" action="" data-status-form>
                @csrf
                @method('PATCH')
                <header class="findings-dialog__header"><div><span class="findings-dialog__icon" aria-hidden="true"><i data-lucide="refresh-cw"></i></span><div><h3 id="finding-status-title">Ubah Status Temuan</h3><p data-status-finding-label>Pilih status terbaru.</p></div></div><button type="button" class="dialog-close" data-dialog-close aria-label="Tutup dialog"><i data-lucide="x" aria-hidden="true"></i></button></header>
                <div class="findings-dialog__body"><label class="field"><span>Status Temuan</span><select name="status" required data-status-select>@foreach ($statusLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label><p class="status-dialog-note"><i data-lucide="info" aria-hidden="true"></i>Status hanya dapat maju bertahap: Open ke Review, lalu Review ke Resolved. Perubahan disimpan atas nama Anda.</p></div>
                <footer class="findings-dialog__footer"><button class="button button--secondary" type="button" data-dialog-close>Batal</button><button class="button button--primary" type="submit">Simpan Status</button></footer>
            </form>
        </dialog>
    @endif

    <dialog class="findings-dialog findings-dialog--photo" id="finding-photo-dialog" aria-labelledby="finding-photo-title">
        <article class="findings-dialog__surface"><header class="findings-dialog__header"><div><span class="findings-dialog__icon" aria-hidden="true"><i data-lucide="image"></i></span><div><h3 id="finding-photo-title">Foto Temuan</h3><p data-photo-dialog-label>Dokumentasi pemeriksaan</p></div></div><button type="button" class="dialog-close" data-dialog-close aria-label="Tutup dialog"><i data-lucide="x" aria-hidden="true"></i></button></header><div class="findings-dialog__body"><div class="finding-photo-preview"><img src="" alt="Foto temuan" data-photo-dialog-image></div></div></article>
    </dialog>

    @if (session('success'))
        <div class="findings-toast is-visible" role="status" aria-live="polite" data-findings-toast><i data-lucide="circle-check" aria-hidden="true"></i><span>{{ session('success') }}</span></div>
    @elseif ($errors->any() && ! ($canCreateFinding && $hasCreateErrors))
        <div class="findings-toast findings-toast--error is-visible" role="alert" data-findings-toast><i data-lucide="circle-alert" aria-hidden="true"></i><span>{{ $errors->first() }}</span></div>
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/findings-database.js') }}" defer></script>
@endpush
