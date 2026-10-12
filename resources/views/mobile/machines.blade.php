@php
    $mobileMachineMode = $mobileMachineMode ?? 'index';
    $mobileMachineIndexUrl = route('mobile.machines');
    $mobileMachineStatusLabels = $statusOptions ?? [
        'active' => 'Aktif',
        'inactive' => 'Nonaktif',
        'maintenance' => 'Maintenance',
    ];
@endphp

<div class="mobile-machines">
    <header class="mobile-machines-header">
        <span class="mobile-machines-header__icon" aria-hidden="true"><i data-lucide="scan-line"></i></span>
        <div>
            <p>Master data peralatan</p>
            <h1>{{ $mobileMachineMode === 'index' ? 'Inventaris Mesin' : ($mobileMachineMode === 'create' ? 'Tambah Mesin' : ($mobileMachineMode === 'edit' ? 'Edit Mesin' : 'Detail Mesin')) }}</h1>
            <span>{{ $mobileMachineMode === 'index' ? 'Kelola mesin X-Ray dari data sistem' : 'Data mesin preventive maintenance X-Ray' }}</span>
        </div>
    </header>

    @if (session('success') || session('error') || $errors->any())
        <div class="mobile-machines-alert {{ session('error') || $errors->any() ? 'mobile-machines-alert--danger' : 'mobile-machines-alert--success' }}" role="alert">
            <i data-lucide="{{ session('error') || $errors->any() ? 'circle-alert' : 'circle-check' }}" aria-hidden="true"></i>
            <span>{{ session('error') ?? session('success') ?? $errors->first() }}</span>
        </div>
    @endif

    @if ($mobileMachineMode === 'index')
        <section class="mobile-machines-panel" aria-labelledby="mobile-machines-filter-title">
            <div class="mobile-machines-section-heading">
                <div><p>Pencarian data</p><h2 id="mobile-machines-filter-title">Filter Mesin</h2></div>
                <i data-lucide="list-filter" aria-hidden="true"></i>
            </div>
            <form class="mobile-machines-filter" method="GET" action="{{ $mobileMachineIndexUrl }}">
                <label class="mobile-machines-field mobile-machines-field--wide">
                    <span>Nama, serial number, atau lokasi</span>
                    <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Cari data mesin" autocomplete="off">
                </label>
                <label class="mobile-machines-field">
                    <span>Status</span>
                    <select name="status">
                        <option value="">Semua status</option>
                        @foreach ($mobileMachineStatusLabels as $statusValue => $statusLabel)
                            <option value="{{ $statusValue }}" @selected($filters['status'] === $statusValue)>{{ $statusLabel }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="mobile-machines-field">
                    <span>Bandar udara</span>
                    <select name="airport">
                        <option value="">Semua bandara</option>
                        @foreach ($airports as $airport)
                            <option value="{{ $airport }}" @selected($filters['airport'] === $airport)>{{ $airport }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="mobile-machines-filter__actions">
                    <button class="mobile-machines-button mobile-machines-button--primary" type="submit"><i data-lucide="search" aria-hidden="true"></i>Terapkan</button>
                    <a class="mobile-machines-button mobile-machines-button--secondary" href="{{ $mobileMachineIndexUrl }}">Reset</a>
                </div>
            </form>
        </section>

        <section class="mobile-machines-summary" aria-label="Ringkasan data mesin">
            <article><i data-lucide="scan-line" aria-hidden="true"></i><div><small>Total Mesin</small><strong>{{ $summary['total'] }}</strong></div></article>
            <article class="is-active"><i data-lucide="circle-check-big" aria-hidden="true"></i><div><small>Aktif</small><strong>{{ $summary['active'] }}</strong></div></article>
            <article class="is-maintenance"><i data-lucide="wrench" aria-hidden="true"></i><div><small>Maintenance</small><strong>{{ $summary['maintenance'] }}</strong></div></article>
            <article class="is-inactive"><i data-lucide="circle-off" aria-hidden="true"></i><div><small>Nonaktif</small><strong>{{ $summary['inactive'] }}</strong></div></article>
        </section>

        <div class="mobile-machines-section-heading mobile-machines-section-heading--outside">
            <div><p>Data database</p><h2>Daftar Mesin</h2></div>
            <span>{{ $machines->total() }} data</span>
        </div>

        <section class="mobile-machines-list" aria-label="Daftar mesin">
            @forelse ($machines as $managedMachine)
                <article class="mobile-machine-card">
                    <header>
                        <span class="mobile-machine-card__icon" aria-hidden="true"><i data-lucide="scan-line"></i></span>
                        <div><h3>{{ $managedMachine->equipment_name }}</h3><code>{{ $managedMachine->serial_number }}</code></div>
                        <span class="machine-status machine-status--{{ $managedMachine->status }}">{{ $mobileMachineStatusLabels[$managedMachine->status] ?? ucfirst($managedMachine->status) }}</span>
                    </header>
                    <dl>
                        <div><dt>Merk / Tipe</dt><dd>{{ $managedMachine->brand }} &middot; {{ $managedMachine->type }}</dd></div>
                        <div><dt>Lokasi</dt><dd>{{ $managedMachine->location }}</dd></div>
                        <div><dt>Bandar Udara</dt><dd>{{ $managedMachine->airport }}</dd></div>
                    </dl>
                    <footer>
                        <a href="{{ route('machines.show', $managedMachine) }}"><i data-lucide="eye" aria-hidden="true"></i>Detail</a>
                        <a href="{{ route('machines.edit', $managedMachine) }}"><i data-lucide="pencil" aria-hidden="true"></i>Edit</a>
                        <form method="POST" action="{{ route('machines.toggle-status', $managedMachine) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="context" value="mobile">
                            <button type="submit"><i data-lucide="power" aria-hidden="true"></i>{{ $managedMachine->status === 'active' ? 'Nonaktif' : 'Aktifkan' }}</button>
                        </form>
                        <form method="POST" action="{{ route('machines.destroy', $managedMachine) }}" onsubmit="return confirm('Hapus mesin ini?')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="context" value="mobile">
                            <button class="is-danger" type="submit"><i data-lucide="trash-2" aria-hidden="true"></i>Hapus</button>
                        </form>
                    </footer>
                </article>
            @empty
                <div class="mobile-machines-empty"><i data-lucide="search-x" aria-hidden="true"></i><h3>Data tidak ditemukan</h3><p>Ubah filter atau tambahkan mesin baru.</p></div>
            @endforelse
        </section>

        @if ($machines->hasPages())
            <nav class="mobile-machines-pagination" aria-label="Paginasi mesin">
                @if ($machines->onFirstPage())<span aria-disabled="true">Sebelumnya</span>@else<a href="{{ $machines->previousPageUrl() }}">Sebelumnya</a>@endif
                <small>{{ $machines->currentPage() }} / {{ $machines->lastPage() }}</small>
                @if ($machines->hasMorePages())<a href="{{ $machines->nextPageUrl() }}">Berikutnya</a>@else<span aria-disabled="true">Berikutnya</span>@endif
            </nav>
        @endif

        <a class="mobile-machines-fab" href="{{ route('machines.create') }}"><i data-lucide="plus" aria-hidden="true"></i><span>Tambah Mesin</span></a>
    @elseif ($mobileMachineMode === 'create' || $mobileMachineMode === 'edit')
        @php($isEditingMachine = $mobileMachineMode === 'edit')
        <section class="mobile-machines-panel">
            <div class="mobile-machines-section-heading">
                <div><p>{{ $isEditingMachine ? 'Perbarui inventaris' : 'Inventaris baru' }}</p><h2>{{ $isEditingMachine ? 'Form Edit Mesin' : 'Form Tambah Mesin' }}</h2></div>
                <i data-lucide="{{ $isEditingMachine ? 'pencil' : 'plus-circle' }}" aria-hidden="true"></i>
            </div>
            <form class="mobile-machines-record-form" method="POST" action="{{ $isEditingMachine ? route('machines.update', $machine) : route('machines.store') }}">
                @csrf
                @if ($isEditingMachine) @method('PUT') @endif
                <input type="hidden" name="context" value="mobile">
                <label class="mobile-machines-field"><span>Nama Peralatan</span><input type="text" name="equipment_name" value="{{ old('equipment_name', $isEditingMachine ? $machine->equipment_name : '') }}" maxlength="255" required>@error('equipment_name')<small class="mobile-machines-error">{{ $message }}</small>@enderror</label>
                <div class="mobile-machines-form-row">
                    <label class="mobile-machines-field"><span>Merk</span><input type="text" name="brand" value="{{ old('brand', $isEditingMachine ? $machine->brand : '') }}" maxlength="255" required>@error('brand')<small class="mobile-machines-error">{{ $message }}</small>@enderror</label>
                    <label class="mobile-machines-field"><span>Tipe</span><input type="text" name="type" value="{{ old('type', $isEditingMachine ? $machine->type : '') }}" maxlength="255" required>@error('type')<small class="mobile-machines-error">{{ $message }}</small>@enderror</label>
                </div>
                <label class="mobile-machines-field"><span>Serial Number</span><input type="text" name="serial_number" value="{{ old('serial_number', $isEditingMachine ? $machine->serial_number : '') }}" maxlength="255" required>@error('serial_number')<small class="mobile-machines-error">{{ $message }}</small>@enderror</label>
                <div class="mobile-machines-form-row">
                    <label class="mobile-machines-field"><span>Lokasi</span><input type="text" name="location" value="{{ old('location', $isEditingMachine ? $machine->location : '') }}" maxlength="255" required>@error('location')<small class="mobile-machines-error">{{ $message }}</small>@enderror</label>
                    <label class="mobile-machines-field"><span>Bandar Udara</span><input type="text" name="airport" value="{{ old('airport', $isEditingMachine ? $machine->airport : 'UPBU Malikussaleh') }}" maxlength="255" required>@error('airport')<small class="mobile-machines-error">{{ $message }}</small>@enderror</label>
                </div>
                <label class="mobile-machines-field"><span>Status</span><select name="status" required>@foreach ($mobileMachineStatusLabels as $statusValue => $statusLabel)<option value="{{ $statusValue }}" @selected(old('status', $isEditingMachine ? $machine->status : 'active') === $statusValue)>{{ $statusLabel }}</option>@endforeach</select>@error('status')<small class="mobile-machines-error">{{ $message }}</small>@enderror</label>
                <label class="mobile-machines-field"><span>Deskripsi <small>(opsional)</small></span><textarea name="description" maxlength="5000" rows="4">{{ old('description', $isEditingMachine ? $machine->description : '') }}</textarea>@error('description')<small class="mobile-machines-error">{{ $message }}</small>@enderror</label>
                <div class="mobile-machines-record-actions">
                    <a class="mobile-machines-button mobile-machines-button--secondary" href="{{ $mobileMachineIndexUrl }}">Batal</a>
                    <button class="mobile-machines-button mobile-machines-button--primary" type="submit"><i data-lucide="save" aria-hidden="true"></i>{{ $isEditingMachine ? 'Simpan Perubahan' : 'Simpan Mesin' }}</button>
                </div>
            </form>
        </section>
    @elseif ($mobileMachineMode === 'show')
        <section class="mobile-machines-panel mobile-machine-detail">
            <div class="mobile-machine-detail__identity">
                <span aria-hidden="true"><i data-lucide="scan-line"></i></span>
                <div><small>Nama peralatan</small><h2>{{ $machine->equipment_name }}</h2><code>{{ $machine->serial_number }}</code></div>
                <span class="machine-status machine-status--{{ $machine->status }}">{{ $mobileMachineStatusLabels[$machine->status] ?? ucfirst($machine->status) }}</span>
            </div>
            <dl class="mobile-machine-detail__grid">
                <div><dt>Merk</dt><dd>{{ $machine->brand }}</dd></div>
                <div><dt>Tipe</dt><dd>{{ $machine->type }}</dd></div>
                <div><dt>Lokasi</dt><dd>{{ $machine->location }}</dd></div>
                <div><dt>Bandar Udara</dt><dd>{{ $machine->airport }}</dd></div>
                <div><dt>Jadwal</dt><dd>{{ $machine->schedules_count }}</dd></div>
                <div><dt>Pemeriksaan</dt><dd>{{ $machine->checks_count }}</dd></div>
                <div><dt>Temuan</dt><dd>{{ $machine->findings_count }}</dd></div>
                <div><dt>Dibuat</dt><dd>{{ $machine->created_at->format('d M Y') }}</dd></div>
                <div class="is-wide"><dt>Deskripsi</dt><dd>{{ $machine->description ?: '-' }}</dd></div>
            </dl>
            <div class="mobile-machines-detail-actions">
                <a class="mobile-machines-button mobile-machines-button--secondary" href="{{ $mobileMachineIndexUrl }}">Kembali</a>
                <a class="mobile-machines-button mobile-machines-button--primary" href="{{ route('machines.edit', $machine) }}"><i data-lucide="pencil" aria-hidden="true"></i>Edit</a>
                <form method="POST" action="{{ route('machines.toggle-status', $machine) }}">@csrf @method('PATCH')<input type="hidden" name="context" value="mobile"><button class="mobile-machines-button mobile-machines-button--secondary" type="submit"><i data-lucide="power" aria-hidden="true"></i>{{ $machine->status === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
                <form method="POST" action="{{ route('machines.destroy', $machine) }}" onsubmit="return confirm('Hapus mesin ini?')">@csrf @method('DELETE')<input type="hidden" name="context" value="mobile"><button class="mobile-machines-button mobile-machines-button--danger" type="submit"><i data-lucide="trash-2" aria-hidden="true"></i>Hapus</button></form>
            </div>
        </section>
    @endif
</div>
