@php
    $mobileReportMonths = [
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

    $mobileReportActivities = [
        [
            'key' => 'lead-curtain',
            'name' => 'Lead curtain',
            'criteria' => 'Tidak robek dan menutup dengan baik.',
            'conform' => 26,
            'nonconform' => 1,
            'not_scheduled' => 4,
            'status' => 'Perlu Review',
            'status_modifier' => 'review',
        ],
        [
            'key' => 'conveyor-belt',
            'name' => 'Conveyor belt',
            'criteria' => 'Berjalan normal dan tidak tersendat.',
            'conform' => 28,
            'nonconform' => 0,
            'not_scheduled' => 3,
            'status' => 'Normal',
            'status_modifier' => 'normal',
        ],
        [
            'key' => 'ups',
            'name' => 'UPS',
            'criteria' => 'Backup daya bekerja baik.',
            'conform' => 24,
            'nonconform' => 2,
            'not_scheduled' => 5,
            'status' => 'Ada Temuan',
            'status_modifier' => 'finding',
        ],
    ];

    $resolveMobileTableStatus = static function (string $activity, int $day): string {
        if ($activity === 'lead-curtain') {
            return match ($day) {
                1, 2, 6, 7, 8 => 'conform',
                3 => 'not-scheduled',
                4 => 'nonconform',
                default => 'pending',
            };
        }

        if ($activity === 'conveyor-belt') {
            if ($day === 5 || $day > 8) {
                return 'pending';
            }

            return $day === 6 ? 'not-scheduled' : 'conform';
        }

        if ($day === 4) {
            return 'nonconform';
        }

        if ($day === 5) {
            return 'pending';
        }

        if (! in_array($day, [7, 14, 21, 28], true)) {
            return 'not-scheduled';
        }

        return $day === 7 ? 'nonconform' : 'pending';
    };
@endphp

<div class="mobile-report" data-mobile-report>
    <header class="mobile-report-header">
        <span class="mobile-report-header__icon" aria-hidden="true"><i data-lucide="file-chart-column"></i></span>
        <div>
            <p>Dokumen Maintenance</p>
            <h1>Laporan</h1>
            <span>Ringkasan laporan preventive maintenance</span>
        </div>
    </header>

    <section class="mobile-report-filter" aria-labelledby="mobile-report-filter-title">
        <header>
            <span aria-hidden="true"><i data-lucide="list-filter"></i></span>
            <div><h2 id="mobile-report-filter-title">Filter Laporan</h2><p>Pilih mesin dan periode laporan.</p></div>
        </header>

        <form data-mobile-report-filter>
            <label class="mobile-report-field">
                <span>Mesin</span>
                <select name="machine">
                    <option value="baggage">X-Ray Baggage HI-Scan 100100T</option>
                    <option value="cabin">X-Ray Cabin</option>
                    <option value="cargo">X-Ray Cargo</option>
                </select>
            </label>
            <div class="mobile-report-filter__period">
                <label class="mobile-report-field">
                    <span>Bulan</span>
                    <select name="month">
                        @foreach ($mobileReportMonths as $monthNumber => $monthName)
                            <option value="{{ $monthNumber }}" @selected($monthNumber === 10)>{{ $monthName }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="mobile-report-field">
                    <span>Tahun</span>
                    <select name="year">
                        @foreach ([2025, 2026, 2027] as $year)
                            <option value="{{ $year }}" @selected($year === 2026)>{{ $year }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div class="mobile-report-filter__actions">
                <button type="submit"><i data-lucide="file-search" aria-hidden="true"></i>Tampilkan</button>
                <button type="reset" data-mobile-report-reset><i data-lucide="rotate-ccw" aria-hidden="true"></i>Reset</button>
            </div>
        </form>
    </section>

    <section class="mobile-report-overview" aria-labelledby="mobile-report-overview-title">
        <header>
            <div><p>Ringkasan Laporan</p><h2 id="mobile-report-overview-title" data-mobile-report-machine>X-Ray Baggage HI-Scan 100100T</h2><span data-mobile-report-period>Oktober 2026</span></div>
            <span aria-hidden="true"><i data-lucide="scan-line"></i></span>
        </header>
        <dl class="mobile-report-overview__metrics">
            <div><dt>Total kegiatan</dt><dd data-mobile-report-total-activities>12</dd></div>
            <div><dt>Pemeriksaan selesai</dt><dd data-mobile-report-completed>186</dd></div>
            <div><dt>Sesuai</dt><dd data-mobile-report-conform>172</dd></div>
            <div><dt>Tidak sesuai</dt><dd data-mobile-report-nonconform>14</dd></div>
            <div><dt>Tidak dijadwalkan</dt><dd data-mobile-report-not-scheduled>42</dd></div>
        </dl>
        <div class="mobile-report-overview__completion">
            <div><span>Penyelesaian</span><strong data-mobile-report-percentage>92%</strong></div>
            <div class="mobile-report-progress" role="progressbar" aria-label="Penyelesaian laporan" aria-valuemin="0" aria-valuemax="100" aria-valuenow="92" data-mobile-report-progress>
                <span data-mobile-report-progress-bar></span>
            </div>
        </div>
    </section>

    <section class="mobile-report-view" aria-labelledby="mobile-report-view-title">
        <div class="mobile-report-section-heading">
            <div><p>Mode Tampilan</p><h2 id="mobile-report-view-title">Pilih Format Laporan</h2></div>
        </div>

        <div class="mobile-report-tabs" role="tablist" aria-label="Mode tampilan laporan">
            <button class="is-active" id="mobile-report-tab-summary" type="button" role="tab" aria-selected="true" aria-controls="mobile-report-panel-summary" data-mobile-report-mode="summary">Ringkasan</button>
            <button id="mobile-report-tab-detail" type="button" role="tab" aria-selected="false" aria-controls="mobile-report-panel-detail" data-mobile-report-mode="detail">Detail Item</button>
            <button id="mobile-report-tab-table" type="button" role="tab" aria-selected="false" aria-controls="mobile-report-panel-table" data-mobile-report-mode="table">Tabel Scroll</button>
        </div>

        <div class="mobile-report-panel" id="mobile-report-panel-summary" role="tabpanel" aria-labelledby="mobile-report-tab-summary" data-mobile-report-panel="summary">
            <div class="mobile-report-section-heading">
                <div><p>Hasil Per Kegiatan</p><h2>Ringkasan Item</h2></div>
                <span>{{ count($mobileReportActivities) }} kegiatan</span>
            </div>

            <div class="mobile-report-activity-list">
                @foreach ($mobileReportActivities as $activity)
                    <article class="mobile-report-activity-card" data-mobile-report-activity="{{ $activity['key'] }}">
                        <header>
                            <span aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <div><h3>{{ $activity['name'] }}</h3><p>{{ $activity['criteria'] }}</p></div>
                            <span class="mobile-report-badge mobile-report-badge--{{ $activity['status_modifier'] }}">{{ $activity['status'] }}</span>
                        </header>
                        <dl>
                            <div><dt>Sesuai</dt><dd>{{ $activity['conform'] }}</dd></div>
                            <div><dt>Tidak sesuai</dt><dd>{{ $activity['nonconform'] }}</dd></div>
                            <div><dt>Tidak dijadwalkan</dt><dd>{{ $activity['not_scheduled'] }}</dd></div>
                        </dl>
                        <button type="button" data-mobile-report-detail="{{ $activity['key'] }}">Lihat Detail<i data-lucide="arrow-right" aria-hidden="true"></i></button>
                    </article>
                @endforeach
            </div>
        </div>

        <div class="mobile-report-panel mobile-report-detail" id="mobile-report-panel-detail" role="tabpanel" aria-labelledby="mobile-report-tab-detail" data-mobile-report-panel="detail" hidden>
            <header class="mobile-report-detail__header">
                <button type="button" data-mobile-report-detail-close aria-label="Tutup detail item"><i data-lucide="arrow-left" aria-hidden="true"></i></button>
                <div><p>Detail Kegiatan</p><h2 data-mobile-report-detail-name>Lead curtain</h2></div>
                <span class="mobile-report-badge mobile-report-badge--review" data-mobile-report-detail-status>Perlu Review</span>
            </header>
            <div class="mobile-report-detail__criteria"><span>Kriteria</span><p data-mobile-report-detail-criteria>Tidak robek dan menutup dengan baik.</p><small data-mobile-report-detail-period>Oktober 2026</small></div>
            <dl class="mobile-report-detail__metrics">
                <div><dt>Sesuai</dt><dd data-mobile-report-detail-conform>26</dd></div>
                <div><dt>Tidak sesuai</dt><dd data-mobile-report-detail-nonconform>1</dd></div>
                <div><dt>Tidak dijadwalkan</dt><dd data-mobile-report-detail-not-scheduled>4</dd></div>
            </dl>
            <section aria-labelledby="mobile-report-date-list-title">
                <div class="mobile-report-section-heading"><div><p>Riwayat Harian</p><h2 id="mobile-report-date-list-title">Tanggal Pemeriksaan</h2></div></div>
                <div class="mobile-report-date-grid" data-mobile-report-date-grid></div>
            </section>
        </div>

        <div class="mobile-report-panel mobile-report-table-panel" id="mobile-report-panel-table" role="tabpanel" aria-labelledby="mobile-report-tab-table" data-mobile-report-panel="table" hidden>
            <div class="mobile-report-table-panel__heading">
                <div><h2>Format Tanggal 1–31</h2><p>Geser ke samping untuk melihat tanggal lainnya.</p></div>
                <i data-lucide="move-horizontal" aria-hidden="true"></i>
            </div>
            <div class="mobile-report-table-scroll" tabindex="0" aria-label="Tabel laporan bulanan, dapat digeser secara horizontal" data-mobile-report-table-scroll>
                <table class="mobile-report-table">
                    <thead>
                        <tr>
                            <th scope="col">No</th>
                            <th scope="col">Kegiatan</th>
                            <th scope="col">Kriteria</th>
                            @for ($day = 1; $day <= 31; $day++)
                                <th scope="col">{{ $day }}</th>
                            @endfor
                            <th scope="col">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($mobileReportActivities as $activity)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><strong>{{ $activity['name'] }}</strong></td>
                                <td>{{ $activity['criteria'] }}</td>
                                @for ($day = 1; $day <= 31; $day++)
                                    @php($tableStatus = $resolveMobileTableStatus($activity['key'], $day))
                                    <td>
                                        <span class="mobile-report-symbol mobile-report-symbol--{{ $tableStatus }}" aria-label="{{ match ($tableStatus) { 'conform' => 'Sesuai', 'nonconform' => 'Tidak sesuai', 'not-scheduled' => 'Tidak dijadwalkan', default => 'Belum diperiksa' } }}">
                                            @switch($tableStatus)
                                                @case('conform') &#10003; @break
                                                @case('nonconform') &times; @break
                                                @case('not-scheduled') &minus; @break
                                                @default &bull;
                                            @endswitch
                                        </span>
                                    </td>
                                @endfor
                                <td>{{ $activity['status'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="mobile-report-legend" aria-labelledby="mobile-report-legend-title">
        <div class="mobile-report-section-heading"><div><p>Keterangan</p><h2 id="mobile-report-legend-title">Legend Simbol</h2></div></div>
        <div>
            <span><i class="mobile-report-symbol mobile-report-symbol--conform" aria-hidden="true">&#10003;</i>Sesuai</span>
            <span><i class="mobile-report-symbol mobile-report-symbol--nonconform" aria-hidden="true">&times;</i>Tidak sesuai</span>
            <span><i class="mobile-report-symbol mobile-report-symbol--not-scheduled" aria-hidden="true">&minus;</i>Tidak dijadwalkan</span>
            <span><i class="mobile-report-symbol mobile-report-symbol--pending" aria-hidden="true">&bull;</i>Belum diperiksa</span>
        </div>
    </section>

    <section class="mobile-report-export" aria-label="Aksi laporan">
        <button type="button" data-mobile-report-action="pdf"><i data-lucide="file-text" aria-hidden="true"></i><span><strong>Cetak PDF</strong><small>Format dokumen</small></span></button>
        <button type="button" data-mobile-report-action="excel"><i data-lucide="sheet" aria-hidden="true"></i><span><strong>Export Excel</strong><small>Format spreadsheet</small></span></button>
    </section>

    <aside class="mobile-report-note">
        <i data-lucide="info" aria-hidden="true"></i>
        <p>Tampilan mobile difokuskan untuk membaca laporan. Untuk cetak penuh format 1–31, gunakan tampilan desktop.</p>
    </aside>

    <div class="mobile-report-toast" role="status" aria-live="polite" data-mobile-report-toast hidden>
        <i data-lucide="circle-check" aria-hidden="true"></i>
        <span data-mobile-report-toast-message></span>
        <button type="button" data-mobile-report-toast-close aria-label="Tutup notifikasi"><i data-lucide="x" aria-hidden="true"></i></button>
    </div>
</div>
