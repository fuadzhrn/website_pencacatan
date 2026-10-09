@php
    $mobileSchedules = [
        [
            'id' => 'schedule-1',
            'month' => '10',
            'year' => '2026',
            'machine_key' => 'baggage',
            'machine' => 'X-Ray Baggage HI-Scan 100100T',
            'brand' => 'Smiths Detection',
            'type' => 'HI-Scan 100100T',
            'serial_number' => 'SD-HS100100T-0426',
            'location' => 'Terminal 1',
            'date' => '09 Okt 2026',
            'time' => '08:00',
            'frequency' => 'Harian',
            'status' => 'scheduled',
            'status_label' => 'Terjadwal',
            'officer' => 'Budi Santoso',
            'note' => 'Pemeriksaan harian area conveyor, lead curtain, monitor, dan fungsi keselamatan unit.',
        ],
        [
            'id' => 'schedule-2',
            'month' => '10',
            'year' => '2026',
            'machine_key' => 'cabin',
            'machine' => 'X-Ray Cabin',
            'brand' => 'Rapiscan Systems',
            'type' => '620XR',
            'serial_number' => 'RS-620XR-1187',
            'location' => 'Terminal 2',
            'date' => '09 Okt 2026',
            'time' => '09:30',
            'frequency' => 'Harian',
            'status' => 'completed',
            'status_label' => 'Selesai',
            'officer' => 'Andi Pratama',
            'note' => 'Pemeriksaan telah dilakukan. Seluruh item checklist dinyatakan sesuai.',
        ],
        [
            'id' => 'schedule-3',
            'month' => '10',
            'year' => '2026',
            'machine_key' => 'cargo',
            'machine' => 'X-Ray Cargo',
            'brand' => 'Astrophysics',
            'type' => 'XIS-100XDV',
            'serial_number' => 'AP-XIS100-0731',
            'location' => 'Area Cargo',
            'date' => '10 Okt 2026',
            'time' => '13:00',
            'frequency' => 'Mingguan',
            'status' => 'overdue',
            'status_label' => 'Terlambat',
            'officer' => 'Siti Rahma',
            'note' => 'Jadwal memerlukan tindak lanjut dan konfirmasi kesiapan area operasional.',
        ],
    ];

    $mobileScheduleStatusIcons = [
        'scheduled' => 'calendar-clock',
        'completed' => 'circle-check-big',
        'pending' => 'clock-3',
        'overdue' => 'triangle-alert',
    ];
@endphp

<main class="mobile-schedules" data-mobile-schedules>
    <header class="mobile-schedules-header">
        <span class="mobile-schedules-header__icon" aria-hidden="true">
            <i data-lucide="calendar-days"></i>
        </span>
        <div>
            <p>Agenda preventive maintenance</p>
            <h1>Jadwal Maintenance</h1>
            <span>Pantau jadwal pemeriksaan mesin X-Ray</span>
        </div>
    </header>

    <section class="mobile-schedules-filter" aria-labelledby="mobile-schedules-filter-title">
        <header class="mobile-schedules-section-heading">
            <div>
                <p>Pencarian ringkas</p>
                <h2 id="mobile-schedules-filter-title">Filter Jadwal</h2>
            </div>
            <i data-lucide="list-filter" aria-hidden="true"></i>
        </header>

        <form data-mobile-schedules-filter>
            <div class="mobile-schedules-filter__grid">
                <label class="mobile-schedules-field">
                    <span>Bulan</span>
                    <select name="month">
                        <option value="">Semua bulan</option>
                        <option value="9">September</option>
                        <option value="10" selected>Oktober</option>
                        <option value="11">November</option>
                    </select>
                </label>

                <label class="mobile-schedules-field">
                    <span>Tahun</span>
                    <select name="year">
                        <option value="">Semua tahun</option>
                        <option value="2025">2025</option>
                        <option value="2026" selected>2026</option>
                        <option value="2027">2027</option>
                    </select>
                </label>
            </div>

            <label class="mobile-schedules-field">
                <span>Mesin</span>
                <select name="machine">
                    <option value="">Semua mesin</option>
                    <option value="baggage">X-Ray Baggage HI-Scan 100100T</option>
                    <option value="cabin">X-Ray Cabin</option>
                    <option value="cargo">X-Ray Cargo</option>
                </select>
            </label>

            <label class="mobile-schedules-field">
                <span>Status</span>
                <select name="status">
                    <option value="">Semua status</option>
                    <option value="scheduled">Terjadwal</option>
                    <option value="completed">Selesai</option>
                    <option value="pending">Belum Dikerjakan</option>
                    <option value="overdue">Terlambat</option>
                </select>
            </label>

            <div class="mobile-schedules-filter__actions">
                <button type="submit">
                    <i data-lucide="search" aria-hidden="true"></i>
                    Terapkan
                </button>
                <button type="button" data-mobile-schedules-reset>
                    <i data-lucide="rotate-ccw" aria-hidden="true"></i>
                    Reset
                </button>
            </div>
        </form>
    </section>

    <section class="mobile-schedules-summary" aria-labelledby="mobile-schedules-summary-title">
        <header class="mobile-schedules-section-heading">
            <div>
                <p>Sesuai filter aktif</p>
                <h2 id="mobile-schedules-summary-title">Ringkasan Jadwal</h2>
            </div>
        </header>

        <div class="mobile-schedules-summary__grid">
            <article class="mobile-schedules-summary-card mobile-schedules-summary-card--total">
                <span aria-hidden="true"><i data-lucide="calendar-range"></i></span>
                <div><small>Total Jadwal</small><strong data-mobile-schedules-summary="total">3</strong></div>
            </article>
            <article class="mobile-schedules-summary-card mobile-schedules-summary-card--scheduled">
                <span aria-hidden="true"><i data-lucide="calendar-clock"></i></span>
                <div><small>Terjadwal</small><strong data-mobile-schedules-summary="scheduled">1</strong></div>
            </article>
            <article class="mobile-schedules-summary-card mobile-schedules-summary-card--completed">
                <span aria-hidden="true"><i data-lucide="circle-check-big"></i></span>
                <div><small>Selesai</small><strong data-mobile-schedules-summary="completed">1</strong></div>
            </article>
            <article class="mobile-schedules-summary-card mobile-schedules-summary-card--overdue">
                <span aria-hidden="true"><i data-lucide="triangle-alert"></i></span>
                <div><small>Terlambat</small><strong data-mobile-schedules-summary="overdue">1</strong></div>
            </article>
        </div>
    </section>

    <section class="mobile-schedules-agenda" aria-labelledby="mobile-schedules-list-title">
        <header class="mobile-schedules-section-heading mobile-schedules-section-heading--list">
            <div>
                <p>Daftar agenda</p>
                <h2 id="mobile-schedules-list-title">Jadwal Maintenance</h2>
            </div>
            <span data-mobile-schedules-result-count>3 jadwal ditampilkan</span>
        </header>

        <div class="mobile-schedules-list" data-mobile-schedules-list>
            @foreach ($mobileSchedules as $schedule)
                <article
                    class="mobile-schedule-card"
                    data-mobile-schedule-card
                    data-id="{{ $schedule['id'] }}"
                    data-month="{{ $schedule['month'] }}"
                    data-year="{{ $schedule['year'] }}"
                    data-machine="{{ $schedule['machine_key'] }}"
                    data-status="{{ $schedule['status'] }}"
                    data-machine-name="{{ $schedule['machine'] }}"
                    data-brand="{{ $schedule['brand'] }}"
                    data-type="{{ $schedule['type'] }}"
                    data-serial-number="{{ $schedule['serial_number'] }}"
                    data-location="{{ $schedule['location'] }}"
                    data-date="{{ $schedule['date'] }}"
                    data-time="{{ $schedule['time'] }}"
                    data-frequency="{{ $schedule['frequency'] }}"
                    data-officer="{{ $schedule['officer'] }}"
                    data-status-label="{{ $schedule['status_label'] }}"
                    data-note="{{ $schedule['note'] }}"
                >
                    <header>
                        <span class="mobile-schedule-card__machine-icon" aria-hidden="true">
                            <i data-lucide="scan-line"></i>
                        </span>
                        <div>
                            <p>{{ $schedule['location'] }}</p>
                            <h3>{{ $schedule['machine'] }}</h3>
                        </div>
                        <span class="mobile-schedules-badge mobile-schedules-badge--{{ $schedule['status'] }}">
                            <i data-lucide="{{ $mobileScheduleStatusIcons[$schedule['status']] }}" aria-hidden="true"></i>
                            {{ $schedule['status_label'] }}
                        </span>
                    </header>

                    <div class="mobile-schedule-card__date">
                        <span aria-hidden="true"><i data-lucide="calendar-days"></i></span>
                        <div><small>Tanggal</small><strong>{{ $schedule['date'] }}</strong></div>
                        <div><small>Jam</small><strong>{{ $schedule['time'] }}</strong></div>
                    </div>

                    <dl class="mobile-schedule-card__meta">
                        <div>
                            <dt><i data-lucide="repeat-2" aria-hidden="true"></i>Frekuensi</dt>
                            <dd>{{ $schedule['frequency'] }}</dd>
                        </div>
                        <div>
                            <dt><i data-lucide="user-round" aria-hidden="true"></i>Petugas</dt>
                            <dd>{{ $schedule['officer'] }}</dd>
                        </div>
                    </dl>

                    <footer>
                        <button type="button" data-mobile-schedule-detail-open data-schedule-id="{{ $schedule['id'] }}">
                            <i data-lucide="eye" aria-hidden="true"></i>
                            Detail
                        </button>
                        <button type="button" data-mobile-schedule-start data-schedule-id="{{ $schedule['id'] }}">
                            <i data-lucide="play" aria-hidden="true"></i>
                            Mulai
                        </button>
                    </footer>
                </article>
            @endforeach
        </div>

        <div class="mobile-schedules-empty" data-mobile-schedules-empty hidden>
            <span aria-hidden="true"><i data-lucide="calendar-search"></i></span>
            <h3>Jadwal tidak ditemukan</h3>
            <p>Ubah filter atau tekan Reset untuk menampilkan kembali semua jadwal.</p>
        </div>
    </section>

    <div class="mobile-schedule-sheet" data-mobile-schedule-sheet aria-hidden="true" hidden>
        <button
            class="mobile-schedule-sheet__overlay"
            type="button"
            data-mobile-schedule-sheet-close
            tabindex="-1"
            aria-label="Tutup detail jadwal"
        ></button>
        <section
            class="mobile-schedule-sheet__panel"
            data-mobile-schedule-sheet-panel
            role="dialog"
            aria-modal="true"
            aria-labelledby="mobile-schedule-detail-title"
            tabindex="-1"
        >
            <div class="mobile-schedule-sheet__handle" aria-hidden="true"></div>
            <header>
                <div>
                    <p>Detail jadwal maintenance</p>
                    <h2 id="mobile-schedule-detail-title" data-mobile-schedule-detail="machineName">Nama mesin</h2>
                </div>
                <button type="button" data-mobile-schedule-sheet-close aria-label="Tutup detail jadwal">
                    <i data-lucide="x" aria-hidden="true"></i>
                </button>
            </header>

            <div class="mobile-schedule-sheet__status">
                <span class="mobile-schedules-badge" data-mobile-schedule-detail="status">Status</span>
            </div>

            <dl class="mobile-schedule-sheet__details">
                <div><dt>Merk</dt><dd data-mobile-schedule-detail="brand">-</dd></div>
                <div><dt>Tipe</dt><dd data-mobile-schedule-detail="type">-</dd></div>
                <div><dt>Serial number</dt><dd data-mobile-schedule-detail="serialNumber">-</dd></div>
                <div><dt>Lokasi</dt><dd data-mobile-schedule-detail="location">-</dd></div>
                <div><dt>Tanggal jadwal</dt><dd data-mobile-schedule-detail="date">-</dd></div>
                <div><dt>Frekuensi</dt><dd data-mobile-schedule-detail="frequency">-</dd></div>
                <div><dt>Petugas</dt><dd data-mobile-schedule-detail="officer">-</dd></div>
            </dl>

            <section class="mobile-schedule-sheet__note">
                <span aria-hidden="true"><i data-lucide="notebook-pen"></i></span>
                <div><h3>Catatan</h3><p data-mobile-schedule-detail="note">-</p></div>
            </section>

            <button class="mobile-schedule-sheet__close-button" type="button" data-mobile-schedule-sheet-close>
                Tutup Detail
            </button>
        </section>
    </div>

    <div class="mobile-schedules-toast" data-mobile-schedules-toast role="status" aria-live="polite" hidden>
        <span aria-hidden="true"><i data-lucide="circle-check-big"></i></span>
        <p data-mobile-schedules-toast-message></p>
        <button type="button" data-mobile-schedules-toast-close aria-label="Tutup notifikasi">
            <i data-lucide="x" aria-hidden="true"></i>
        </button>
    </div>
</main>
