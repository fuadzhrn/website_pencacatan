@php
    $statusClass = static fn (string $status): string => match ($status) {
        'completed' => 'completed',
        'overdue' => 'overdue',
        'pending' => 'pending',
        'in_progress' => 'progress',
        'cancelled' => 'cancelled',
        default => 'scheduled',
    };
    $editingSchedule = request('form') === 'edit' ? $selectedSchedule : null;
@endphp

<div class="schedules-page" data-schedules-page>
    @if (session('success'))
        <div class="schedule-notice schedule-notice--success" role="status">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="schedule-notice schedule-notice--error" role="alert">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="schedule-notice schedule-notice--error" role="alert">
            <strong>Data belum dapat disimpan.</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="schedules-hero" aria-labelledby="schedule-page-heading">
        <div>
            <p class="schedules-hero__eyebrow">Perencanaan preventive maintenance</p>
            <h2 id="schedule-page-heading">Jadwal Maintenance</h2>
            <p>Kelola jadwal pemeriksaan mesin X-Ray berdasarkan frekuensi maintenance.</p>
        </div>
        @if ($canManage)
            <div class="schedules-hero__actions">
                <a class="button button--secondary" href="{{ route('schedules.index', ['form' => 'generate']) }}">
                    <i data-lucide="calendar-cog" aria-hidden="true"></i> Generate Jadwal
                </a>
                <a class="button button--primary" href="{{ route('schedules.create') }}">
                    <i data-lucide="plus" aria-hidden="true"></i> Tambah Jadwal
                </a>
            </div>
        @endif
    </section>

    @if ($canManage && request('form') === 'generate')
        <section class="schedules-filter" aria-labelledby="generate-schedule-title">
            <header><h2 id="generate-schedule-title">Generate Jadwal Otomatis</h2><p>Jadwal dibuat mengikuti frekuensi kategori dalam rentang tanggal inklusif.</p></header>
            <form method="POST" action="{{ route('schedules.generate') }}" class="schedules-filter__grid">
                @csrf
                <input type="hidden" name="form_type" value="generate">
                <label>Mesin<select name="machine_id" required><option value="">Pilih mesin</option>@foreach ($machines as $machine)<option value="{{ $machine->id }}" @selected(old('machine_id') == $machine->id)>{{ $machine->equipment_name }} — {{ $machine->serial_number }}</option>@endforeach</select></label>
                <label>Kategori<select name="maintenance_category_id" required><option value="">Pilih kategori</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected(old('maintenance_category_id') == $category->id)>{{ $category->name }} ({{ $frequencyOptions[$category->frequency] ?? $category->frequency }})</option>@endforeach</select></label>
                <label>Tanggal Mulai<input type="date" name="start_date" value="{{ old('start_date') }}" required></label>
                <label>Tanggal Akhir<input type="date" name="end_date" value="{{ old('end_date') }}" required></label>
                <label>Jam<input type="time" name="scheduled_time" value="{{ old('scheduled_time') }}"></label>
                <label>Petugas<select name="assigned_user_id"><option value="">Belum ditentukan</option>@foreach ($petugas as $officer)<option value="{{ $officer->id }}" @selected(old('assigned_user_id') == $officer->id)>{{ $officer->name }}</option>@endforeach</select></label>
                <label class="schedules-filter__wide">Catatan<textarea name="notes" rows="3">{{ old('notes') }}</textarea></label>
                <div class="schedules-filter__actions"><button class="button button--primary" type="submit">Generate</button><a class="button button--secondary" href="{{ route('schedules.index') }}">Batal</a></div>
            </form>
        </section>
    @endif

    @if ($canManage && in_array(request('form'), ['create', 'edit'], true))
        <section class="schedules-filter" aria-labelledby="schedule-form-title">
            <header><h2 id="schedule-form-title">{{ $editingSchedule ? 'Edit Jadwal' : 'Tambah Jadwal' }}</h2></header>
            <form method="POST" action="{{ $editingSchedule ? route('schedules.update', $editingSchedule) : route('schedules.store') }}" class="schedules-filter__grid">
                @csrf
                @if ($editingSchedule) @method('PUT') @endif
                <input type="hidden" name="form_type" value="schedule">
                <label>Mesin<select name="machine_id" required><option value="">Pilih mesin</option>@foreach ($machines as $machine)<option value="{{ $machine->id }}" @selected(old('machine_id', $editingSchedule?->machine_id) == $machine->id)>{{ $machine->equipment_name }}</option>@endforeach</select></label>
                <label>Kategori<select name="maintenance_category_id" required><option value="">Pilih kategori</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected(old('maintenance_category_id', $editingSchedule?->maintenance_category_id) == $category->id)>{{ $category->name }}</option>@endforeach</select></label>
                <label>Tanggal<input type="date" name="scheduled_date" value="{{ old('scheduled_date', $editingSchedule?->scheduled_date?->toDateString()) }}" required></label>
                <label>Jam<input type="time" name="scheduled_time" value="{{ old('scheduled_time', $editingSchedule?->scheduled_time ? substr($editingSchedule->scheduled_time, 0, 5) : null) }}"></label>
                <label>Petugas<select name="assigned_user_id"><option value="">Belum ditentukan</option>@foreach ($petugas as $officer)<option value="{{ $officer->id }}" @selected(old('assigned_user_id', $editingSchedule?->assigned_user_id) == $officer->id)>{{ $officer->name }}</option>@endforeach</select></label>
                <label>Status<select name="status" required>@foreach ($statusOptions as $value => $label)<option value="{{ $value }}" @selected(old('status', $editingSchedule?->status ?? 'scheduled') === $value)>{{ $label }}</option>@endforeach</select></label>
                <label class="schedules-filter__wide">Catatan<textarea name="notes" rows="3">{{ old('notes', $editingSchedule?->notes) }}</textarea></label>
                <div class="schedules-filter__actions"><button class="button button--primary" type="submit">Simpan</button><a class="button button--secondary" href="{{ route('schedules.index') }}">Batal</a></div>
            </form>
        </section>
    @endif

    <section class="schedule-summary" aria-label="Ringkasan jadwal">
        @foreach ([['Total Jadwal', $summary['total'], 'calendar-days'], ['Terjadwal', $summary['scheduled'], 'calendar-clock'], ['Selesai', $summary['completed'], 'circle-check'], ['Terlambat', $summary['overdue'], 'triangle-alert']] as [$label, $value, $icon])
            <article class="schedule-summary-card"><span><i data-lucide="{{ $icon }}" aria-hidden="true"></i></span><div><p>{{ $label }}</p><strong>{{ $value }}</strong></div></article>
        @endforeach
    </section>

    <section class="schedules-filter" aria-labelledby="schedule-filter-title">
        <header><h2 id="schedule-filter-title">Filter Jadwal</h2></header>
        <form method="GET" action="{{ route('schedules.index') }}" class="schedules-filter__grid">
            <label>Bulan<select name="month"><option value="">Semua bulan</option>@foreach (range(1, 12) as $month)<option value="{{ $month }}" @selected((string) $filters['month'] === (string) $month)>{{ \Carbon\CarbonImmutable::create(2026, $month)->translatedFormat('F') }}</option>@endforeach</select></label>
            <label>Tahun<input type="number" name="year" min="2020" max="2100" value="{{ $filters['year'] }}" placeholder="Semua tahun"></label>
            <label>Mesin<select name="machine"><option value="">Semua mesin</option>@foreach ($machines as $machine)<option value="{{ $machine->id }}" @selected((string) $filters['machine_id'] === (string) $machine->id)>{{ $machine->equipment_name }}</option>@endforeach</select></label>
            <label>Frekuensi<select name="frequency"><option value="">Semua frekuensi</option>@foreach ($frequencyOptions as $value => $label)<option value="{{ $value }}" @selected($filters['frequency'] === $value)>{{ $label }}</option>@endforeach</select></label>
            <label>Status<select name="status"><option value="">Semua status</option>@foreach ($statusOptions as $value => $label)<option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>@endforeach</select></label>
            <label>Cari<input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Mesin, serial, lokasi, petugas"></label>
            <div class="schedules-filter__actions"><button class="button button--primary" type="submit">Terapkan</button><a class="button button--secondary" href="{{ route('schedules.index') }}">Reset</a></div>
        </form>
    </section>

    <section class="schedules-table-panel" aria-labelledby="schedule-list-title">
        <header class="schedules-table-panel__header"><div><h2 id="schedule-list-title">Daftar Jadwal</h2><p>{{ $schedules->total() }} jadwal ditemukan.</p></div></header>
        <div class="schedules-table-wrap">
            <table class="schedules-table">
                <thead><tr><th>No.</th><th>Tanggal</th><th>Mesin</th><th>Lokasi</th><th>Frekuensi</th><th>Petugas</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse ($schedules as $schedule)
                        <tr>
                            <td>{{ $schedules->firstItem() + $loop->index }}</td>
                            <td><time datetime="{{ $schedule->scheduled_date->toDateString() }}"><strong>{{ $schedule->scheduled_date->translatedFormat('d F Y') }}</strong><span>{{ $schedule->scheduled_time ? substr($schedule->scheduled_time, 0, 5).' WITA' : 'Jam belum ditentukan' }}</span></time></td>
                            <td><strong>{{ $schedule->machine->equipment_name }}</strong><small>{{ $schedule->machine->serial_number }}</small></td>
                            <td>{{ $schedule->machine->location }}</td>
                            <td>{{ $frequencyOptions[$schedule->category->frequency] ?? $schedule->category->frequency }}<small>{{ $schedule->category->name }}</small></td>
                            <td>{{ $schedule->assignedUser?->name ?? 'Belum ditentukan' }}</td>
                            <td><span class="schedule-status schedule-status--{{ $statusClass($schedule->status) }}">{{ $statusOptions[$schedule->status] ?? $schedule->status }}</span></td>
                            <td><div class="schedules-actions">
                                <a class="schedule-action schedule-action--detail" href="{{ route('schedules.show', $schedule) }}"><i data-lucide="eye" aria-hidden="true"></i> Detail</a>
                                @if ($canManage)
                                    <a class="schedule-action schedule-action--edit" href="{{ route('schedules.edit', $schedule) }}"><i data-lucide="pencil" aria-hidden="true"></i> Edit</a>
                                    <form method="POST" action="{{ route('schedules.destroy', $schedule) }}" onsubmit="return confirm('Hapus jadwal ini?')">@csrf @method('DELETE')<button class="schedule-action" type="submit"><i data-lucide="trash-2" aria-hidden="true"></i> Hapus</button></form>
                                @endif
                                @if (auth()->user()->role === \App\UserRole::Petugas && in_array($schedule->status, ['scheduled', 'pending'], true))
                                    <form method="POST" action="{{ route('schedules.update-status', $schedule) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="in_progress"><button class="schedule-action schedule-action--start" type="submit"><i data-lucide="play" aria-hidden="true"></i> Mulai</button></form>
                                @endif
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><div class="schedules-empty"><i data-lucide="calendar-x" aria-hidden="true"></i><strong>Jadwal tidak ditemukan</strong><span>Ubah filter atau tambahkan jadwal baru.</span></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <footer class="schedules-table-panel__footer">{{ $schedules->links() }}</footer>
    </section>

    @if ($selectedSchedule && request('form') !== 'edit')
        <section class="today-schedules" aria-labelledby="schedule-detail-title">
            <header class="today-schedules__header"><div><p class="today-schedules__eyebrow">Detail Jadwal</p><h2 id="schedule-detail-title">{{ $selectedSchedule->machine->equipment_name }}</h2></div><a class="button button--secondary" href="{{ route('schedules.index') }}">Tutup</a></header>
            <dl class="schedule-dialog__details">
                <div><dt>Merk / Tipe</dt><dd>{{ $selectedSchedule->machine->brand }} / {{ $selectedSchedule->machine->type }}</dd></div>
                <div><dt>Serial Number</dt><dd>{{ $selectedSchedule->machine->serial_number }}</dd></div>
                <div><dt>Lokasi</dt><dd>{{ $selectedSchedule->machine->location }}</dd></div>
                <div><dt>Tanggal</dt><dd>{{ $selectedSchedule->scheduled_date->translatedFormat('d F Y') }}</dd></div>
                <div><dt>Frekuensi</dt><dd>{{ $frequencyOptions[$selectedSchedule->category->frequency] ?? $selectedSchedule->category->frequency }}</dd></div>
                <div><dt>Petugas</dt><dd>{{ $selectedSchedule->assignedUser?->name ?? 'Belum ditentukan' }}</dd></div>
                <div><dt>Status</dt><dd>{{ $statusOptions[$selectedSchedule->status] ?? $selectedSchedule->status }}</dd></div>
                <div class="schedule-dialog__wide"><dt>Catatan</dt><dd>{{ $selectedSchedule->notes ?: '-' }}</dd></div>
            </dl>
        </section>
    @endif

    <section class="today-schedules" aria-labelledby="today-schedules-title">
        <header class="today-schedules__header"><div><p class="today-schedules__eyebrow">Agenda Operasional</p><h2 id="today-schedules-title">Jadwal Hari Ini</h2><p>{{ today()->translatedFormat('l, d F Y') }} · {{ $todaySchedules->count() }} agenda</p></div></header>
        <div class="today-schedules__list">
            @forelse ($todaySchedules as $schedule)
                <article class="today-schedule-item"><span class="today-schedule-item__icon"><i data-lucide="scan-line" aria-hidden="true"></i></span><div class="today-schedule-item__content"><h3>{{ $schedule->machine->equipment_name }}</h3><p>{{ $schedule->category->name }} · {{ $schedule->machine->location }}</p></div><time>{{ $schedule->scheduled_time ? substr($schedule->scheduled_time, 0, 5).' WITA' : '-' }}</time><span class="schedule-status schedule-status--{{ $statusClass($schedule->status) }}">{{ $statusOptions[$schedule->status] ?? $schedule->status }}</span></article>
            @empty
                <p>Belum ada jadwal untuk hari ini.</p>
            @endforelse
        </div>
    </section>
</div>
