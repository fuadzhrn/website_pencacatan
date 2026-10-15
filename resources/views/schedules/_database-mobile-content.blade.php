@php
    $mobileStatusClass = static fn (string $status): string => match ($status) {
        'completed' => 'completed',
        'overdue' => 'overdue',
        'pending' => 'pending',
        'in_progress' => 'pending',
        'cancelled' => 'inactive',
        default => 'scheduled',
    };
    $editingSchedule = request('form') === 'edit' ? $selectedSchedule : null;
@endphp

<main class="mobile-schedules" data-mobile-schedules>
    <header class="mobile-schedules-header">
        <span class="mobile-schedules-header__icon" aria-hidden="true"><i data-lucide="calendar-days"></i></span>
        <div><p>Agenda preventive maintenance</p><h1>Jadwal Maintenance</h1><span>Pantau jadwal pemeriksaan mesin X-Ray</span></div>
    </header>

    @if (session('success'))<div class="mobile-schedules-toast is-visible" role="status"><i data-lucide="circle-check"></i><p>{{ session('success') }}</p></div>@endif
    @if (session('error'))<div class="mobile-schedules-toast is-visible" role="alert"><i data-lucide="triangle-alert"></i><p>{{ session('error') }}</p></div>@endif
    @if ($errors->any())<div class="mobile-schedules-toast is-visible" role="alert"><i data-lucide="triangle-alert"></i><p>{{ $errors->first() }}</p></div>@endif

    @if ($canManage && request('form') === 'generate')
        <section class="mobile-schedules-filter"><header class="mobile-schedules-section-heading"><div><p>Otomatis</p><h2>Generate Jadwal</h2></div></header>
            <form method="POST" action="{{ route('schedules.generate') }}">@csrf<input type="hidden" name="context" value="mobile"><input type="hidden" name="form_type" value="generate">
                <div class="mobile-schedules-filter__grid">
                    <label class="mobile-schedules-field">Mesin<select name="machine_id" required><option value="">Pilih mesin</option>@foreach ($machines as $machine)<option value="{{ $machine->id }}" @selected(old('machine_id') == $machine->id)>{{ $machine->equipment_name }}</option>@endforeach</select></label>
                    <label class="mobile-schedules-field">Kategori<select name="maintenance_category_id" required><option value="">Pilih kategori</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected(old('maintenance_category_id') == $category->id)>{{ $category->name }}</option>@endforeach</select></label>
                    <label class="mobile-schedules-field">Tanggal mulai<input type="date" name="start_date" value="{{ old('start_date') }}" required></label>
                    <label class="mobile-schedules-field">Tanggal akhir<input type="date" name="end_date" value="{{ old('end_date') }}" required></label>
                    <label class="mobile-schedules-field">Jam<input type="time" name="scheduled_time" value="{{ old('scheduled_time') }}"></label>
                    <label class="mobile-schedules-field">Petugas<select name="assigned_user_id"><option value="">Belum ditentukan</option>@foreach ($petugas as $officer)<option value="{{ $officer->id }}" @selected(old('assigned_user_id') == $officer->id)>{{ $officer->name }}</option>@endforeach</select></label>
                </div>
                <label class="mobile-schedules-field">Catatan<textarea name="notes" rows="3">{{ old('notes') }}</textarea></label>
                <div class="mobile-schedules-filter__actions"><button type="submit"><i data-lucide="calendar-cog"></i> Generate</button><a href="{{ route('mobile.schedules') }}">Batal</a></div>
            </form>
        </section>
    @endif

    @if ($canManage && in_array(request('form'), ['create', 'edit'], true))
        <section class="mobile-schedules-filter"><header class="mobile-schedules-section-heading"><div><p>Form Jadwal</p><h2>{{ $editingSchedule ? 'Edit Jadwal' : 'Tambah Jadwal' }}</h2></div></header>
            <form method="POST" action="{{ $editingSchedule ? route('schedules.update', $editingSchedule) : route('schedules.store') }}">@csrf @if($editingSchedule) @method('PUT') @endif<input type="hidden" name="context" value="mobile"><input type="hidden" name="form_type" value="schedule">
                <div class="mobile-schedules-filter__grid">
                    @if($editingSchedule)
                        <label class="mobile-schedules-field">Mesin<input type="text" value="{{ $editingSchedule->machine->equipment_name }}" disabled></label>
                        <label class="mobile-schedules-field">Kategori<input type="text" value="{{ $editingSchedule->maintenanceCategory->name }}" disabled></label>
                    @else
                        <label class="mobile-schedules-field">Mesin<select name="machine_id" required><option value="">Pilih mesin</option>@foreach ($machines as $machine)<option value="{{ $machine->id }}" @selected(old('machine_id') == $machine->id)>{{ $machine->equipment_name }}</option>@endforeach</select></label>
                        <label class="mobile-schedules-field">Kategori<select name="maintenance_category_id" required><option value="">Pilih kategori</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected(old('maintenance_category_id') == $category->id)>{{ $category->name }}</option>@endforeach</select></label>
                    @endif
                    <label class="mobile-schedules-field">Tanggal<input type="date" name="scheduled_date" value="{{ old('scheduled_date', $editingSchedule?->scheduled_date?->toDateString()) }}" required></label>
                    <label class="mobile-schedules-field">Jam<input type="time" name="scheduled_time" value="{{ old('scheduled_time', $editingSchedule?->scheduled_time ? substr($editingSchedule->scheduled_time, 0, 5) : null) }}"></label>
                    <label class="mobile-schedules-field">Petugas<select name="assigned_user_id"><option value="">Belum ditentukan</option>@foreach ($petugas as $officer)<option value="{{ $officer->id }}" @selected(old('assigned_user_id', $editingSchedule?->assigned_user_id) == $officer->id)>{{ $officer->name }}</option>@endforeach</select></label>
                    <label class="mobile-schedules-field">Status<select name="status" required>@foreach ($statusOptions as $value => $label)<option value="{{ $value }}" @selected(old('status', $editingSchedule?->status ?? 'scheduled') === $value)>{{ $label }}</option>@endforeach</select></label>
                </div>
                <label class="mobile-schedules-field">Catatan<textarea name="notes" rows="3">{{ old('notes', $editingSchedule?->notes) }}</textarea></label>
                <div class="mobile-schedules-filter__actions"><button type="submit"><i data-lucide="save"></i> Simpan</button><a href="{{ route('mobile.schedules') }}">Batal</a></div>
            </form>
        </section>
    @endif

    <section class="mobile-schedules-filter" aria-labelledby="mobile-schedules-filter-title"><header class="mobile-schedules-section-heading"><div><p>Penyaringan data</p><h2 id="mobile-schedules-filter-title">Filter Jadwal</h2></div></header>
        <form method="GET" action="{{ route('mobile.schedules') }}"><div class="mobile-schedules-filter__grid">
            <label class="mobile-schedules-field">Bulan<select name="month"><option value="">Semua bulan</option>@foreach(range(1, 12) as $month)<option value="{{ $month }}" @selected((string)$filters['month'] === (string)$month)>{{ \Carbon\CarbonImmutable::create(2026, $month)->translatedFormat('F') }}</option>@endforeach</select></label>
            <label class="mobile-schedules-field">Tahun<input type="number" name="year" value="{{ $filters['year'] }}" placeholder="Semua tahun"></label>
            <label class="mobile-schedules-field">Mesin<select name="machine_id"><option value="">Semua mesin</option>@foreach($machines as $machine)<option value="{{ $machine->id }}" @selected((string)$filters['machine_id'] === (string)$machine->id)>{{ $machine->equipment_name }}</option>@endforeach</select></label>
            <label class="mobile-schedules-field">Kategori<select name="maintenance_category_id"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string)$filters['maintenance_category_id'] === (string)$category->id)>{{ $category->name }}</option>@endforeach</select></label>
            <label class="mobile-schedules-field">Status<select name="status"><option value="">Semua status</option>@foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>@endforeach</select></label>
            @if(auth()->user()->role !== \App\UserRole::Petugas)<label class="mobile-schedules-field">Petugas<select name="assigned_user_id"><option value="">Semua petugas</option>@foreach($petugas as $officer)<option value="{{ $officer->id }}" @selected((string)$filters['assigned_user_id'] === (string)$officer->id)>{{ $officer->name }}</option>@endforeach</select></label>@endif
        </div><label class="mobile-schedules-field">Cari<input type="search" name="search" value="{{ $filters['search'] }}" placeholder="Mesin, serial, lokasi, petugas"></label><div class="mobile-schedules-filter__actions"><button type="submit"><i data-lucide="search"></i> Terapkan</button><a href="{{ route('mobile.schedules') }}">Reset</a></div></form>
    </section>

    <section class="mobile-schedules-summary" aria-labelledby="mobile-schedules-summary-title"><header class="mobile-schedules-section-heading"><div><p>Statistik periode</p><h2 id="mobile-schedules-summary-title">Ringkasan Jadwal</h2></div></header><div class="mobile-schedules-summary__grid">
        @foreach([['Total Jadwal', $summary['total'], 'total', 'calendar-days'], ['Terjadwal', $summary['scheduled'], 'scheduled', 'calendar-clock'], ['Selesai', $summary['completed'], 'completed', 'circle-check'], ['Terlambat', $summary['overdue'], 'overdue', 'triangle-alert']] as [$label, $value, $class, $icon])<article class="mobile-schedules-summary-card mobile-schedules-summary-card--{{ $class }}"><span><i data-lucide="{{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ $value }}</strong></div></article>@endforeach
    </div></section>

    <section class="mobile-schedules-agenda" aria-labelledby="mobile-schedules-list-title"><header class="mobile-schedules-section-heading mobile-schedules-section-heading--list"><div><p>Agenda tersimpan</p><h2 id="mobile-schedules-list-title">Jadwal Maintenance</h2></div><span>{{ $schedules->total() }} jadwal</span></header>
        <div class="mobile-schedules-list">
            @forelse($schedules as $schedule)
                <article class="mobile-schedule-card"><div class="mobile-schedule-card__top"><span class="mobile-schedule-card__icon"><i data-lucide="scan-line"></i></span><div><h3>{{ $schedule->machine->equipment_name }}</h3><p><i data-lucide="map-pin"></i> {{ $schedule->machine->location }} · {{ $schedule->machine->airport }}</p></div><span class="mobile-schedules-badge mobile-schedules-badge--{{ $mobileStatusClass($schedule->status) }}">{{ $statusOptions[$schedule->status] ?? $schedule->status }}</span></div>
                    <dl class="mobile-schedule-card__meta"><div><dt>Tanggal</dt><dd>{{ $schedule->scheduled_date->translatedFormat('d M Y') }}</dd></div><div><dt>Jam</dt><dd>{{ $schedule->scheduled_time ? substr($schedule->scheduled_time, 0, 5) : '-' }}</dd></div><div><dt>Frekuensi</dt><dd>{{ $frequencyOptions[$schedule->maintenanceCategory->frequency] ?? $schedule->maintenanceCategory->frequency }}</dd></div><div><dt>Petugas</dt><dd>{{ $schedule->assignedUser?->name ?? 'Belum ditentukan' }}</dd></div></dl>
                    <div class="mobile-schedule-card__actions"><a href="{{ route('schedules.show', ['schedule' => $schedule, 'context' => 'mobile']) }}"><i data-lucide="eye"></i> Detail</a>
                        @if($canManage)<a href="{{ route('mobile.schedules', ['form' => 'edit', 'schedule_detail' => $schedule->id]) }}"><i data-lucide="pencil"></i> Edit</a><form method="POST" action="{{ route('schedules.destroy', $schedule) }}" onsubmit="return confirm('Hapus jadwal ini?')">@csrf @method('DELETE')<input type="hidden" name="context" value="mobile"><button type="submit"><i data-lucide="trash-2"></i> Hapus</button></form>@endif
                        @if(auth()->user()->role === \App\UserRole::Petugas && $schedule->scheduled_date->isToday())
                            @if(! $schedule->check && in_array($schedule->status, ['scheduled', 'pending', 'in_progress', 'overdue'], true))<form method="POST" action="{{ route('inspections.start', $schedule) }}">@csrf<input type="hidden" name="context" value="mobile"><button type="submit"><i data-lucide="play"></i> Mulai</button></form>
                            @elseif($schedule->status === 'in_progress')<a href="{{ route('mobile.inspection-today', ['schedule' => $schedule->id]) }}"><i data-lucide="play"></i> Lanjutkan</a>@endif
                        @endif
                    </div>
                </article>
            @empty<div class="mobile-schedules-empty"><i data-lucide="calendar-x"></i><strong>Jadwal tidak ditemukan</strong><span>Ubah filter atau tambahkan jadwal baru.</span></div>@endforelse
        </div>@include('partials.pagination', ['paginator' => $schedules, 'class' => 'mobile-schedules-pagination', 'label' => 'Navigasi halaman jadwal'])
    </section>

    @if($selectedSchedule && request('form') !== 'edit')
        <div class="mobile-schedule-sheet is-open" aria-hidden="false">
            <a class="mobile-schedule-sheet__overlay" href="{{ route('mobile.schedules') }}" aria-label="Tutup detail jadwal"></a>
            <section class="mobile-schedule-sheet__panel" role="dialog" aria-modal="true" aria-labelledby="database-schedule-detail-title" tabindex="-1">
                <div class="mobile-schedule-sheet__handle" aria-hidden="true"></div>
                <header><div><p>Detail jadwal maintenance</p><h2 id="database-schedule-detail-title">{{ $selectedSchedule->machine->equipment_name }}</h2></div><a href="{{ route('mobile.schedules') }}" aria-label="Tutup detail jadwal"><i data-lucide="x"></i></a></header>
                <div class="mobile-schedule-sheet__status"><span class="mobile-schedules-badge mobile-schedules-badge--{{ $mobileStatusClass($selectedSchedule->status) }}">{{ $statusOptions[$selectedSchedule->status] ?? $selectedSchedule->status }}</span></div>
                <dl class="mobile-schedule-sheet__details"><div><dt>Merk</dt><dd>{{ $selectedSchedule->machine->brand }}</dd></div><div><dt>Tipe</dt><dd>{{ $selectedSchedule->machine->type }}</dd></div><div><dt>Serial Number</dt><dd>{{ $selectedSchedule->machine->serial_number }}</dd></div><div><dt>Lokasi</dt><dd>{{ $selectedSchedule->machine->location }}</dd></div><div><dt>Bandara</dt><dd>{{ $selectedSchedule->machine->airport }}</dd></div><div><dt>Tanggal</dt><dd>{{ $selectedSchedule->scheduled_date->translatedFormat('d F Y') }}</dd></div><div><dt>Frekuensi</dt><dd>{{ $frequencyOptions[$selectedSchedule->maintenanceCategory->frequency] ?? $selectedSchedule->maintenanceCategory->frequency }}</dd></div><div><dt>Petugas</dt><dd>{{ $selectedSchedule->assignedUser?->name ?? 'Belum ditentukan' }}</dd></div></dl>
                <section class="mobile-schedule-sheet__note"><span><i data-lucide="notebook-pen"></i></span><div><h3>Catatan</h3><p>{{ $selectedSchedule->notes ?: '-' }}</p></div></section>
                @if($selectedSchedule->check)<section class="mobile-schedule-sheet__note"><span><i data-lucide="clipboard-check"></i></span><div><h3>Informasi Pemeriksaan</h3><p>Hasil: {{ $selectedSchedule->check->overall_status }} · Verifikasi: {{ $selectedSchedule->check->verification_status }}</p><p>{{ $selectedSchedule->check->general_note ?: '-' }}</p></div></section>@endif
                <a class="mobile-schedule-sheet__close-button" href="{{ route('mobile.schedules') }}">Tutup Detail</a>
            </section>
        </div>
    @endif

    @if($canManage)<div class="mobile-schedules-fab"><a href="{{ route('mobile.schedules', ['form' => 'generate']) }}"><i data-lucide="calendar-cog"></i><span>Generate</span></a><a href="{{ route('mobile.schedules', ['form' => 'create']) }}"><i data-lucide="plus"></i><span>Tambah Jadwal</span></a></div>@endif
</main>
