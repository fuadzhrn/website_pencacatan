@extends('layouts.app')

@section('title', 'Laporan Bulanan')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/monthly-report.css') }}">
@endpush

@php
    $months = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    $activities = [
        ['name' => 'Lead curtain', 'criteria' => 'Tidak robek, tidak aus, dan menutup dengan baik.', 'frequency' => 'daily', 'note' => 'Normal'],
        ['name' => 'Conveyor belt', 'criteria' => 'Berjalan normal, tidak tersendat, dan tidak miring.', 'frequency' => 'daily', 'note' => 'Ditemukan kendala pada conveyor'],
        ['name' => 'Emergency stop', 'criteria' => 'Berfungsi saat ditekan dan menghentikan mesin.', 'frequency' => 'weekly', 'note' => 'Sudah ditindaklanjuti'],
        ['name' => 'Indicator lamp', 'criteria' => 'Lampu indikator menyala sesuai kondisi mesin.', 'frequency' => 'daily', 'note' => 'Tidak ada catatan'],
        ['name' => 'Monitor', 'criteria' => 'Tampilan gambar jelas dan tidak buram.', 'frequency' => 'daily', 'note' => 'Perlu pengecekan lanjutan'],
        ['name' => 'UPS', 'criteria' => 'Backup daya bekerja dengan baik.', 'frequency' => 'weekly', 'note' => 'Normal'],
        ['name' => 'Keyboard control', 'criteria' => 'Tombol kontrol berfungsi normal.', 'frequency' => 'weekly', 'note' => 'Tidak ada catatan'],
        ['name' => 'Zoom-in dan Zoom-out', 'criteria' => 'Fungsi pembesaran gambar berjalan normal.', 'frequency' => 'monthly', 'note' => 'Normal'],
        ['name' => 'Roller', 'criteria' => 'Roller bersih dan berputar dengan baik.', 'frequency' => 'weekly', 'note' => 'Perlu pembersihan berkala'],
        ['name' => 'X-Ray generator', 'criteria' => 'Tidak ada alarm atau error pada sistem.', 'frequency' => 'monthly', 'note' => 'Tidak ada catatan'],
        ['name' => 'Cleanliness unit', 'criteria' => 'Area unit bersih dari debu dan kotoran.', 'frequency' => 'daily', 'note' => 'Perlu pembersihan berkala'],
        ['name' => 'Functional test', 'criteria' => 'Mesin dapat digunakan sesuai fungsi utama.', 'frequency' => 'daily', 'note' => 'Normal'],
    ];
@endphp

@section('content')
    <div class="monthly-report-page">
        <header class="monthly-report-header">
            <div class="monthly-report-header__identity">
                <span class="monthly-report-header__icon" aria-hidden="true">
                    <i data-lucide="file-chart-column"></i>
                </span>
                <div>
                    <p class="monthly-report-header__eyebrow">Dokumen Maintenance</p>
                    <h2>Laporan Bulanan</h2>
                    <p>Rekap hasil preventive maintenance mesin X-Ray berdasarkan bulan dan tahun.</p>
                </div>
            </div>
            <div class="monthly-report-header__actions">
                <button class="report-button report-button--secondary" type="button" data-export-report>
                    <i data-lucide="sheet" aria-hidden="true"></i>
                    Export Excel
                </button>
                <button class="report-button report-button--primary" type="button" data-print-report>
                    <i data-lucide="printer" aria-hidden="true"></i>
                    Cetak PDF
                </button>
            </div>
        </header>

        <section class="report-filter-panel" aria-labelledby="report-filter-title">
            <div class="report-section-heading">
                <span class="report-section-heading__icon" aria-hidden="true"><i data-lucide="list-filter"></i></span>
                <div>
                    <h3 id="report-filter-title">Filter Laporan</h3>
                    <p>Pilih mesin dan periode laporan yang ingin ditampilkan.</p>
                </div>
            </div>
            <form class="report-filter" data-report-filter>
                <label class="report-field report-filter__machine">
                    <span>Mesin</span>
                    <select name="machine" data-filter-machine>
                        <option value="baggage">X-Ray Baggage Smiths Detection HI-Scan 100100T</option>
                        <option value="cabin">X-Ray Cabin</option>
                        <option value="cargo">X-Ray Cargo</option>
                    </select>
                </label>
                <label class="report-field">
                    <span>Bulan</span>
                    <select name="month" data-filter-month>
                        @foreach ($months as $monthNumber => $monthName)
                            <option value="{{ $monthNumber }}" @selected($monthNumber === 10)>{{ $monthName }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="report-field">
                    <span>Tahun</span>
                    <select name="year" data-filter-year>
                        @foreach ([2025, 2026, 2027] as $year)
                            <option value="{{ $year }}" @selected($year === 2026)>{{ $year }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="report-filter__actions">
                    <button class="report-button report-button--primary" type="submit">
                        <i data-lucide="file-search" aria-hidden="true"></i>
                        Tampilkan Laporan
                    </button>
                    <button class="report-button report-button--secondary" type="reset" data-reset-report>
                        <i data-lucide="rotate-ccw" aria-hidden="true"></i>
                        Reset
                    </button>
                </div>
            </form>
        </section>

        <article class="monthly-report-document" id="monthly-report-document" data-report-document>
            <header class="report-document-heading">
                <div class="report-document-heading__mark" aria-hidden="true"><i data-lucide="scan-line"></i></div>
                <div>
                    <p>UPBU Malikussaleh</p>
                    <h3>Laporan Preventive Maintenance Bulanan</h3>
                    <span>Sistem Informasi Preventive Maintenance X-Ray</span>
                </div>
                <div class="report-document-heading__period">
                    <small>Periode Laporan</small>
                    <strong><span data-report-month>Oktober</span> <span data-report-year>2026</span></strong>
                </div>
            </header>

            <section class="report-information" aria-labelledby="report-information-title">
                <div class="report-information__title">
                    <span aria-hidden="true"><i data-lucide="info"></i></span>
                    <h4 id="report-information-title">Informasi Laporan</h4>
                </div>
                <dl class="report-information__grid">
                    <div><dt>Nama Laporan</dt><dd>Laporan Preventive Maintenance Bulanan</dd></div>
                    <div><dt>Nama Mesin</dt><dd data-report-machine>X-Ray Baggage Smiths Detection HI-Scan 100100T</dd></div>
                    <div><dt>Merk</dt><dd data-report-brand>Smiths Detection</dd></div>
                    <div><dt>Tipe</dt><dd data-report-type>HI-Scan 100100T</dd></div>
                    <div><dt>Serial Number</dt><dd data-report-serial>SD-HS100100T-021</dd></div>
                    <div><dt>Lokasi</dt><dd data-report-location>Security Check Point Terminal 1</dd></div>
                    <div><dt>Bandar Udara</dt><dd data-report-airport>Bandar Udara Malikussaleh</dd></div>
                    <div><dt>Bulan</dt><dd data-report-month>Oktober</dd></div>
                    <div><dt>Tahun</dt><dd data-report-year>2026</dd></div>
                    <div><dt>Petugas</dt><dd data-report-officer>Budi Santoso</dd></div>
                    <div><dt>Supervisor</dt><dd data-report-supervisor>Ahmad Fauzi</dd></div>
                </dl>
            </section>

            <section class="report-table-section" aria-labelledby="monthly-table-title">
                <div class="report-table-section__heading">
                    <div>
                        <h4 id="monthly-table-title">Rekap Pemeriksaan Harian</h4>
                        <p>Status pemeriksaan setiap kegiatan pada periode terpilih.</p>
                    </div>
                    <div class="report-legend" aria-label="Keterangan simbol laporan">
                        <span><i class="report-symbol report-symbol--completed" aria-hidden="true">✓</i>Sesuai / selesai</span>
                        <span><i class="report-symbol report-symbol--issue" aria-hidden="true">×</i>Tidak sesuai / temuan</span>
                        <span><i class="report-symbol report-symbol--not-scheduled" aria-hidden="true">−</i>Tidak dijadwalkan</span>
                        <span><i class="report-symbol report-symbol--pending" aria-hidden="true">•</i>Belum diperiksa</span>
                    </div>
                </div>

                <div class="monthly-report-table-scroll">
                    <table class="monthly-report-table">
                        <thead>
                            <tr>
                                <th scope="col">No</th>
                                <th scope="col">Kegiatan</th>
                                <th scope="col">Kriteria</th>
                                @for ($day = 1; $day <= 31; $day++)
                                    <th class="monthly-report-table__day" scope="col">{{ $day }}</th>
                                @endfor
                                <th scope="col">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody data-report-body>
                            @foreach ($activities as $activity)
                                <tr data-report-row data-activity-index="{{ $loop->index }}" data-frequency="{{ $activity['frequency'] }}" data-activity="{{ $activity['name'] }}" data-criteria="{{ $activity['criteria'] }}">
                                    <td class="monthly-report-table__number">{{ $loop->iteration }}</td>
                                    <td class="monthly-report-table__activity"><strong>{{ $activity['name'] }}</strong></td>
                                    <td class="monthly-report-table__criteria">{{ $activity['criteria'] }}</td>
                                    @for ($day = 1; $day <= 31; $day++)
                                        <td class="monthly-report-table__status" data-report-day="{{ $day }}">
                                            <span class="report-symbol report-symbol--pending" data-status-symbol aria-label="Belum diperiksa">•</span>
                                        </td>
                                    @endfor
                                    <td class="monthly-report-table__note" data-row-note>{{ $activity['note'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="monthly-summary" aria-labelledby="monthly-summary-title">
                <div class="monthly-summary__heading">
                    <div><h4 id="monthly-summary-title">Ringkasan Bulanan</h4><p>Akumulasi status dari laporan yang sedang ditampilkan.</p></div>
                    <span data-summary-period>Oktober 2026</span>
                </div>
                <div class="monthly-summary__grid">
                    <article><span class="monthly-summary__icon monthly-summary__icon--neutral"><i data-lucide="list-checks"></i></span><div><small>Total Kegiatan</small><strong data-summary-activities>12</strong></div></article>
                    <article><span class="monthly-summary__icon monthly-summary__icon--completed"><i data-lucide="circle-check"></i></span><div><small>Pemeriksaan Sesuai</small><strong data-summary-completed>0</strong></div></article>
                    <article><span class="monthly-summary__icon monthly-summary__icon--issue"><i data-lucide="circle-x"></i></span><div><small>Tidak Sesuai</small><strong data-summary-issue>0</strong></div></article>
                    <article><span class="monthly-summary__icon monthly-summary__icon--muted"><i data-lucide="calendar-off"></i></span><div><small>Tidak Dijadwalkan</small><strong data-summary-not-scheduled>0</strong></div></article>
                    <article><span class="monthly-summary__icon monthly-summary__icon--issue"><i data-lucide="triangle-alert"></i></span><div><small>Total Temuan</small><strong data-summary-findings>0</strong></div></article>
                    <article class="monthly-summary__completion"><span class="monthly-summary__icon monthly-summary__icon--completed"><i data-lucide="gauge"></i></span><div><small>Persentase Penyelesaian</small><strong data-summary-percentage>0%</strong><span class="monthly-summary__progress"><i data-summary-progress></i></span></div></article>
                </div>
            </section>

            <section class="report-signatures" aria-label="Pengesahan laporan">
                <div><span>Dibuat oleh</span><strong>Petugas</strong><div class="report-signatures__space"></div><p data-signature-officer>Budi Santoso</p><small>NIP. <span data-signature-officer-nip>19890214 201503 1 002</span></small></div>
                <div><span>Diperiksa oleh</span><strong>Supervisor</strong><div class="report-signatures__space"></div><p data-signature-supervisor>Ahmad Fauzi</p><small>NIP. <span data-signature-supervisor-nip>19850708 201001 1 006</span></small></div>
                <div><span>Disetujui oleh</span><strong>Admin / Manager</strong><div class="report-signatures__space"></div><p>Rahmat Hidayat</p><small>NIP. 19791222 200501 1 003</small></div>
            </section>
        </article>
    </div>

    <div class="report-toast" role="status" aria-live="polite" data-report-toast hidden>
        <i data-lucide="circle-check" aria-hidden="true"></i>
        <span></span>
    </div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('assets/js/monthly-report.js') }}"></script>
@endpush
