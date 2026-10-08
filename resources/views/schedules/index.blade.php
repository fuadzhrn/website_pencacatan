@extends('layouts.app')

@section('title', 'Jadwal Maintenance')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/schedules.css') }}">
@endpush

@php
    $schedules = [
        [
            'date' => '2026-10-08',
            'date_label' => '08 Oktober 2026',
            'time' => '08:00',
            'machine' => 'X-Ray Baggage Smiths Detection HI-Scan 100100T',
            'machine_key' => 'baggage',
            'location' => 'Terminal 1',
            'frequency' => 'Harian',
            'frequency_key' => 'daily',
            'inspection' => 'Pemeriksaan Safety Check',
            'officer' => 'Budi Santoso',
            'status' => 'Terjadwal',
            'status_key' => 'scheduled',
            'status_modifier' => 'scheduled',
        ],
        [
            'date' => '2026-10-08',
            'date_label' => '08 Oktober 2026',
            'time' => '09:00',
            'machine' => 'X-Ray Cabin',
            'machine_key' => 'cabin',
            'location' => 'Terminal 2',
            'frequency' => 'Harian',
            'frequency_key' => 'daily',
            'inspection' => 'Pemeriksaan Conveyor Belt',
            'officer' => 'Andi Pratama',
            'status' => 'Selesai',
            'status_key' => 'completed',
            'status_modifier' => 'completed',
        ],
        [
            'date' => '2026-10-09',
            'date_label' => '09 Oktober 2026',
            'time' => '10:00',
            'machine' => 'X-Ray Cargo',
            'machine_key' => 'cargo',
            'location' => 'Area Cargo',
            'frequency' => 'Mingguan',
            'frequency_key' => 'weekly',
            'inspection' => 'Pemeriksaan Control Elements',
            'officer' => 'Siti Rahma',
            'status' => 'Belum Dikerjakan',
            'status_key' => 'pending',
            'status_modifier' => 'pending',
        ],
        [
            'date' => '2026-10-05',
            'date_label' => '05 Oktober 2026',
            'time' => '07:30',
            'machine' => 'X-Ray Baggage Smiths Detection HI-Scan 100100T',
            'machine_key' => 'baggage',
            'location' => 'Terminal 1',
            'frequency' => 'Bulanan',
            'frequency_key' => 'monthly',
            'inspection' => 'Pemeriksaan UPS dan Monitor',
            'officer' => 'Rudi Hartono',
            'status' => 'Terlambat',
            'status_key' => 'overdue',
            'status_modifier' => 'overdue',
        ],
        [
            'date' => '2026-10-08',
            'date_label' => '08 Oktober 2026',
            'time' => '10:30',
            'machine' => 'X-Ray Cargo',
            'machine_key' => 'cargo',
            'location' => 'Area Cargo',
            'frequency' => 'Harian',
            'frequency_key' => 'daily',
            'inspection' => 'Leakage Radiation Test',
            'officer' => 'Dewi Lestari',
            'status' => 'Selesai',
            'status_key' => 'completed',
            'status_modifier' => 'completed',
        ],
        [
            'date' => '2026-10-12',
            'date_label' => '12 Oktober 2026',
            'time' => '09:30',
            'machine' => 'X-Ray Cabin',
            'machine_key' => 'cabin',
            'location' => 'Terminal 2',
            'frequency' => 'Mingguan',
            'frequency_key' => 'weekly',
            'inspection' => 'Pemeriksaan Lead Curtain',
            'officer' => 'Andi Pratama',
            'status' => 'Terjadwal',
            'status_key' => 'scheduled',
            'status_modifier' => 'scheduled',
        ],
        [
            'date' => '2026-10-15',
            'date_label' => '15 Oktober 2026',
            'time' => '08:00',
            'machine' => 'X-Ray Baggage Smiths Detection HI-Scan 100100T',
            'machine_key' => 'baggage',
            'location' => 'Terminal 1',
            'frequency' => 'Triwulan',
            'frequency_key' => 'quarterly',
            'inspection' => 'Pemeriksaan Image Orientation',
            'officer' => 'Budi Santoso',
            'status' => 'Belum Dikerjakan',
            'status_key' => 'pending',
            'status_modifier' => 'pending',
        ],
        [
            'date' => '2026-10-20',
            'date_label' => '20 Oktober 2026',
            'time' => '11:00',
            'machine' => 'X-Ray Cargo',
            'machine_key' => 'cargo',
            'location' => 'Area Cargo',
            'frequency' => 'Semesteran',
            'frequency_key' => 'semester',
            'inspection' => 'Pemeriksaan X-Ray Beam Alignment',
            'officer' => 'Siti Rahma',
            'status' => 'Terjadwal',
            'status_key' => 'scheduled',
            'status_modifier' => 'scheduled',
        ],
    ];

    $todaySchedules = array_filter(
        $schedules,
        fn (array $schedule): bool => $schedule['date'] === '2026-10-08',
    );
@endphp

@section('content')
    <div class="schedules-page">
        <header class="schedules-page-header">
            <div>
                <p class="schedules-page-header__eyebrow">Perencanaan Maintenance</p>
                <h2>Jadwal Maintenance</h2>
                <p>Pantau jadwal pemeriksaan preventive maintenance mesin X-Ray.</p>
            </div>
            <button class="button button--primary" type="button" data-schedule-action="create">
                <i data-lucide="calendar-plus" aria-hidden="true"></i>
                Tambah Jadwal
            </button>
        </header>

        <section class="schedules-summary" aria-label="Ringkasan jadwal maintenance">
            <article class="schedules-summary-card schedules-summary-card--total">
                <span class="schedules-summary-card__icon" aria-hidden="true"><i data-lucide="calendar-range"></i></span>
                <div>
                    <p>Total Jadwal</p>
                    <strong>8</strong>
                    <span>Jadwal Oktober 2026</span>
                </div>
            </article>
            <article class="schedules-summary-card schedules-summary-card--scheduled">
                <span class="schedules-summary-card__icon" aria-hidden="true"><i data-lucide="calendar-clock"></i></span>
                <div>
                    <p>Terjadwal</p>
                    <strong>3</strong>
                    <span>Menunggu waktu pemeriksaan</span>
                </div>
            </article>
            <article class="schedules-summary-card schedules-summary-card--completed">
                <span class="schedules-summary-card__icon" aria-hidden="true"><i data-lucide="circle-check-big"></i></span>
                <div>
                    <p>Selesai</p>
                    <strong>2</strong>
                    <span>Pemeriksaan sudah dilakukan</span>
                </div>
            </article>
            <article class="schedules-summary-card schedules-summary-card--overdue">
                <span class="schedules-summary-card__icon" aria-hidden="true"><i data-lucide="calendar-x-2"></i></span>
                <div>
                    <p>Terlambat</p>
                    <strong>1</strong>
                    <span>Perlu segera ditindaklanjuti</span>
                </div>
            </article>
        </section>

        <section class="schedules-filter-panel" aria-labelledby="schedule-filter-title">
            <div class="schedules-filter-panel__heading">
                <span aria-hidden="true"><i data-lucide="list-filter"></i></span>
                <div>
                    <h2 id="schedule-filter-title">Filter Jadwal</h2>
                    <p>Saring jadwal berdasarkan periode dan detail pemeriksaan.</p>
                </div>
            </div>

            <form class="schedules-filter-form" data-schedule-filter>
                <div class="form-field">
                    <label for="schedule-month">Bulan</label>
                    <select class="form-control" id="schedule-month" name="month">
                        <option value="">Semua bulan</option>
                        @foreach (['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] as $monthIndex => $month)
                            <option value="{{ $monthIndex + 1 }}">{{ $month }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field">
                    <label for="schedule-year">Tahun</label>
                    <select class="form-control" id="schedule-year" name="year">
                        <option value="">Semua tahun</option>
                        <option value="2025">2025</option>
                        <option value="2026">2026</option>
                        <option value="2027">2027</option>
                    </select>
                </div>
                <div class="form-field schedules-filter-form__machine">
                    <label for="schedule-machine">Mesin</label>
                    <select class="form-control" id="schedule-machine" name="machine">
                        <option value="">Semua mesin</option>
                        <option value="baggage">X-Ray Baggage Smiths Detection HI-Scan 100100T</option>
                        <option value="cabin">X-Ray Cabin</option>
                        <option value="cargo">X-Ray Cargo</option>
                    </select>
                </div>
                <div class="form-field">
                    <label for="schedule-frequency">Frekuensi</label>
                    <select class="form-control" id="schedule-frequency" name="frequency">
                        <option value="">Semua frekuensi</option>
                        <option value="daily">Harian</option>
                        <option value="weekly">Mingguan</option>
                        <option value="monthly">Bulanan</option>
                        <option value="quarterly">Triwulan</option>
                        <option value="semester">Semesteran</option>
                        <option value="yearly">Tahunan</option>
                    </select>
                </div>
                <div class="form-field">
                    <label for="schedule-status">Status</label>
                    <select class="form-control" id="schedule-status" name="status">
                        <option value="">Semua status</option>
                        <option value="scheduled">Terjadwal</option>
                        <option value="pending">Belum Dikerjakan</option>
                        <option value="completed">Selesai</option>
                        <option value="overdue">Terlambat</option>
                    </select>
                </div>
                <div class="schedules-filter-form__actions">
                    <button class="button button--primary" type="submit">
                        <i data-lucide="search" aria-hidden="true"></i>
                        Terapkan Filter
                    </button>
                    <button class="button button--neutral" type="button" data-filter-reset>
                        <i data-lucide="rotate-ccw" aria-hidden="true"></i>
                        Reset
                    </button>
                </div>
            </form>
        </section>

        <section class="schedules-table-panel" aria-labelledby="schedules-table-title">
            <header class="schedules-table-panel__header">
                <div>
                    <h2 id="schedules-table-title">Daftar Jadwal Maintenance</h2>
                    <p>Rencana pemeriksaan mesin X-Ray untuk periode yang dipilih.</p>
                </div>
                <span class="schedules-table-panel__count" data-schedule-count>8 data</span>
            </header>

            <div class="schedules-table-wrap">
                <table class="schedules-table">
                    <thead>
                        <tr>
                            <th scope="col">No</th>
                            <th scope="col">Tanggal</th>
                            <th scope="col">Nama Mesin</th>
                            <th scope="col">Lokasi</th>
                            <th scope="col">Frekuensi</th>
                            <th scope="col">Jenis Pemeriksaan</th>
                            <th scope="col">Petugas</th>
                            <th scope="col">Status</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($schedules as $index => $schedule)
                            <tr
                                data-schedule-row
                                data-month="{{ (int) substr($schedule['date'], 5, 2) }}"
                                data-year="{{ substr($schedule['date'], 0, 4) }}"
                                data-machine="{{ $schedule['machine_key'] }}"
                                data-frequency="{{ $schedule['frequency_key'] }}"
                                data-status="{{ $schedule['status_key'] }}"
                            >
                                <td><span class="schedules-table__number">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span></td>
                                <td>
                                    <time class="schedule-date" datetime="{{ $schedule['date'] }}">
                                        <strong>{{ $schedule['date_label'] }}</strong>
                                        <span>{{ $schedule['time'] }} WITA</span>
                                    </time>
                                </td>
                                <td>
                                    <div class="schedule-machine">
                                        <span aria-hidden="true"><i data-lucide="scan-line"></i></span>
                                        <strong>{{ $schedule['machine'] }}</strong>
                                    </div>
                                </td>
                                <td>{{ $schedule['location'] }}</td>
                                <td><span class="schedule-frequency">{{ $schedule['frequency'] }}</span></td>
                                <td><span class="schedule-inspection">{{ $schedule['inspection'] }}</span></td>
                                <td>
                                    <span class="schedule-officer"><i data-lucide="user-round" aria-hidden="true"></i>{{ $schedule['officer'] }}</span>
                                </td>
                                <td><span class="schedule-status schedule-status--{{ $schedule['status_modifier'] }}">{{ $schedule['status'] }}</span></td>
                                <td>
                                    <div class="schedules-actions">
                                        @foreach ([['detail', 'eye', 'Detail'], ['edit', 'pencil', 'Edit'], ['start', 'play', 'Mulai Pemeriksaan']] as [$action, $icon, $label])
                                            <button
                                                class="schedule-action schedule-action--{{ $action }}"
                                                type="button"
                                                data-schedule-action="{{ $action }}"
                                                data-machine-name="{{ $schedule['machine'] }}"
                                                data-date-label="{{ $schedule['date_label'] }}"
                                                data-time="{{ $schedule['time'] }} WITA"
                                                data-location="{{ $schedule['location'] }}"
                                                data-frequency-label="{{ $schedule['frequency'] }}"
                                                data-inspection="{{ $schedule['inspection'] }}"
                                                data-officer="{{ $schedule['officer'] }}"
                                                data-status-label="{{ $schedule['status'] }}"
                                                title="{{ $label }}"
                                            >
                                                <i data-lucide="{{ $icon }}" aria-hidden="true"></i>
                                                {{ $label }}
                                            </button>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        <tr class="schedules-empty-row" data-schedule-empty hidden>
                            <td colspan="9">
                                <div>
                                    <i data-lucide="calendar-x" aria-hidden="true"></i>
                                    <strong>Jadwal tidak ditemukan</strong>
                                    <span>Ubah pilihan filter untuk melihat jadwal lainnya.</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <footer class="schedules-table-panel__footer">
                <span data-schedule-range>Menampilkan 1&ndash;8 dari 8 jadwal</span>
                <span>Data dummy &bull; Belum terhubung database</span>
            </footer>
        </section>

        <section class="today-schedules" aria-labelledby="today-schedules-title">
            <header class="today-schedules__header">
                <div>
                    <p class="today-schedules__eyebrow">Agenda Operasional</p>
                    <h2 id="today-schedules-title">Jadwal Hari Ini</h2>
                    <p>Kamis, 08 Oktober 2026 &bull; {{ count($todaySchedules) }} agenda pemeriksaan</p>
                </div>
                <span class="today-schedules__date" aria-hidden="true"><strong>08</strong><small>OKT</small></span>
            </header>
            <div class="today-schedules__list">
                @foreach ($todaySchedules as $schedule)
                    <article class="today-schedule-item">
                        <span class="today-schedule-item__icon" aria-hidden="true"><i data-lucide="scan-line"></i></span>
                        <div class="today-schedule-item__content">
                            <h3>{{ $schedule['machine'] }}</h3>
                            <p>{{ $schedule['inspection'] }} &bull; {{ $schedule['location'] }}</p>
                        </div>
                        <span class="today-schedule-item__frequency">{{ $schedule['frequency'] }}</span>
                        <time datetime="{{ $schedule['date'] }}T{{ $schedule['time'] }}">{{ $schedule['time'] }} WITA</time>
                        <span class="schedule-status schedule-status--{{ $schedule['status_modifier'] }}">{{ $schedule['status'] }}</span>
                    </article>
                @endforeach
            </div>
        </section>
    </div>

    <dialog class="schedule-dialog" id="schedule-action-dialog" aria-labelledby="schedule-dialog-title">
        <div class="schedule-dialog__content">
            <header class="schedule-dialog__header">
                <div class="schedule-dialog__title">
                    <span aria-hidden="true"><i data-dialog-icon data-lucide="calendar-search"></i></span>
                    <div>
                        <p data-dialog-eyebrow>Informasi jadwal</p>
                        <h2 id="schedule-dialog-title" data-dialog-title>Detail Jadwal</h2>
                    </div>
                </div>
                <button class="schedule-dialog__close" type="button" data-dialog-close aria-label="Tutup dialog">
                    <i data-lucide="x" aria-hidden="true"></i>
                </button>
            </header>
            <div class="schedule-dialog__body">
                <p class="schedule-dialog__message" data-dialog-message>Pilih jadwal untuk melihat informasi.</p>
                <dl class="schedule-dialog__details" data-dialog-details>
                    <div><dt>Mesin</dt><dd data-dialog-field="machineName">-</dd></div>
                    <div><dt>Tanggal dan jam</dt><dd><span data-dialog-field="dateLabel">-</span>, <span data-dialog-field="time">-</span></dd></div>
                    <div><dt>Lokasi</dt><dd data-dialog-field="location">-</dd></div>
                    <div><dt>Frekuensi</dt><dd data-dialog-field="frequencyLabel">-</dd></div>
                    <div class="schedule-dialog__wide"><dt>Jenis pemeriksaan</dt><dd data-dialog-field="inspection">-</dd></div>
                    <div><dt>Petugas</dt><dd data-dialog-field="officer">-</dd></div>
                    <div><dt>Status</dt><dd data-dialog-field="statusLabel">-</dd></div>
                </dl>
            </div>
            <footer class="schedule-dialog__footer">
                <span><i data-lucide="info" aria-hidden="true"></i> Simulasi tampilan tanpa penyimpanan data.</span>
                <button class="button button--primary" type="button" data-dialog-close data-dialog-confirm>Tutup</button>
            </footer>
        </div>
    </dialog>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('assets/js/schedules.js') }}"></script>
@endpush
