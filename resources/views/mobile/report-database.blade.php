<div class="mobile-report" data-mobile-report>
    <header class="mobile-report-header">
        <span class="mobile-report-header__icon" aria-hidden="true"><i data-lucide="file-chart-column"></i></span>
        <div><p>Dokumen Maintenance</p><h1>Laporan</h1><span>Ringkasan laporan preventive maintenance</span></div>
    </header>

    <section class="mobile-report-filter" aria-labelledby="mobile-report-filter-title">
        <header><span aria-hidden="true"><i data-lucide="list-filter"></i></span><div><h2 id="mobile-report-filter-title">Filter Laporan</h2><p>Pilih mesin dan periode laporan.</p></div></header>
        @if ($errors->any())
            <div class="mobile-report-validation" role="alert">
                <strong>Filter belum valid.</strong>
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        <form method="GET" action="{{ route('mobile.report') }}">
            <label class="mobile-report-field">
                <span>Mesin</span>
                <select name="machine_id" required>
                    @forelse ($machines as $machine)
                        <option value="{{ $machine->id }}" @selected($selectedMachine?->is($machine))>{{ $machine->equipment_name }}</option>
                    @empty
                        <option value="">Tidak ada mesin yang dapat diakses</option>
                    @endforelse
                </select>
            </label>
            <div class="mobile-report-filter__period">
                <label class="mobile-report-field"><span>Bulan</span><select name="month" required>@foreach ($monthOptions as $number => $name)<option value="{{ $number }}" @selected($selectedMonth === $number)>{{ $name }}</option>@endforeach</select></label>
                <label class="mobile-report-field"><span>Tahun</span><select name="year" required>@foreach ($yearOptions as $year)<option value="{{ $year }}" @selected($selectedYear === $year)>{{ $year }}</option>@endforeach</select></label>
            </div>
            <div class="mobile-report-filter__actions">
                <button type="submit"><i data-lucide="file-search" aria-hidden="true"></i>Tampilkan</button>
                <a href="{{ route('mobile.report') }}"><i data-lucide="rotate-ccw" aria-hidden="true"></i>Reset</a>
            </div>
        </form>
    </section>

    @if (! $selectedMachine)
        <section class="mobile-report-empty"><i data-lucide="scan-search"></i><h2>Belum ada mesin</h2><p>Tidak ada data mesin yang dapat Anda akses.</p></section>
    @else
        <section class="mobile-report-overview" aria-labelledby="mobile-report-overview-title">
            <header><div><p>Ringkasan Laporan</p><h2 id="mobile-report-overview-title">{{ $report['machine']->equipment_name }}</h2><span>{{ $report['period']['label'] }}</span></div><span aria-hidden="true"><i data-lucide="scan-line"></i></span></header>
            <dl class="mobile-report-overview__metrics">
                <div><dt>Total item</dt><dd>{{ $report['summary']['total_items'] }}</dd></div>
                <div><dt>Terjadwal</dt><dd>{{ $report['summary']['scheduled'] }}</dd></div>
                <div><dt>Sesuai</dt><dd>{{ $report['summary']['conform'] }}</dd></div>
                <div><dt>Tidak sesuai</dt><dd>{{ $report['summary']['nonconform'] }}</dd></div>
                <div><dt>Belum diperiksa</dt><dd>{{ $report['summary']['pending'] }}</dd></div>
            </dl>
            <div class="mobile-report-overview__completion">
                <div><span>Penyelesaian</span><strong>{{ $report['summary']['completion_percentage'] }}%</strong></div>
                <div class="mobile-report-progress" role="progressbar" aria-label="Penyelesaian laporan" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $report['summary']['completion_percentage'] }}"><span style="width: {{ $report['summary']['completion_percentage'] }}%"></span></div>
            </div>
        </section>

        @if ($report['groups'] === [])
            <section class="mobile-report-empty"><i data-lucide="calendar-x"></i><h2>Tidak ada jadwal</h2><p>Tidak ada jadwal maintenance untuk mesin dan periode yang dipilih.</p></section>
        @else
            @php
                $mobileReportRows = collect($report['groups'])->flatMap(fn (array $group): array => collect($group['rows'])
                    ->map(fn (array $row): array => [...$row, 'group' => $group])
                    ->all())->values();
                $mobileSymbolState = fn (string $state): string => match ($state) {
                    'conform' => 'conform',
                    'nonconform' => 'nonconform',
                    'pending' => 'pending',
                    default => 'not-scheduled',
                };
                $mobileRowStatus = fn (array $counts): array => match (true) {
                    $counts['nonconform'] > 0 => ['finding', 'Ada Temuan'],
                    $counts['pending'] > 0 => ['review', 'Belum Lengkap'],
                    default => ['normal', 'Normal'],
                };
            @endphp
            <section class="mobile-report-view" aria-labelledby="mobile-report-view-title" data-mobile-report-database>
                <div class="mobile-report-section-heading">
                    <div><p>Mode Tampilan</p><h2 id="mobile-report-view-title">Pilih Format Laporan</h2></div>
                </div>

                <div class="mobile-report-tabs" role="tablist" aria-label="Mode tampilan laporan">
                    <button class="is-active" id="mobile-report-tab-summary" type="button" role="tab" aria-selected="true" aria-controls="mobile-report-panel-summary" data-mobile-report-mode="summary">Ringkasan</button>
                    <button id="mobile-report-tab-detail" type="button" role="tab" aria-selected="false" aria-controls="mobile-report-panel-detail" data-mobile-report-mode="detail" tabindex="-1">Detail Item</button>
                    <button id="mobile-report-tab-table" type="button" role="tab" aria-selected="false" aria-controls="mobile-report-panel-table" data-mobile-report-mode="table" tabindex="-1">Tabel Scroll</button>
                </div>

                <div class="mobile-report-panel" id="mobile-report-panel-summary" role="tabpanel" aria-labelledby="mobile-report-tab-summary" data-mobile-report-panel="summary">
                    <div class="mobile-report-section-heading">
                        <div><p>Hasil Per Kegiatan</p><h2 id="mobile-report-items-title">Ringkasan Item</h2></div>
                        <span>{{ $report['summary']['total_items'] }} item</span>
                    </div>
                    <div class="mobile-report-activity-list">
                        @foreach ($mobileReportRows as $rowIndex => $row)
                            @php [$rowStatusModifier, $rowStatusLabel] = $mobileRowStatus($row['counts']); @endphp
                            <article class="mobile-report-activity-card">
                                <header><span aria-hidden="true">{{ str_pad((string) ($rowIndex + 1), 2, '0', STR_PAD_LEFT) }}</span><div><h3>{{ $row['item']->item_name }}</h3><p>{{ $row['item']->criteria }}</p></div><span class="mobile-report-badge mobile-report-badge--{{ $rowStatusModifier }}">{{ $rowStatusLabel }}</span></header>
                                <dl><div><dt>Sesuai</dt><dd>{{ $row['counts']['conform'] }}</dd></div><div><dt>Tidak sesuai</dt><dd>{{ $row['counts']['nonconform'] }}</dd></div><div><dt>Belum diperiksa</dt><dd>{{ $row['counts']['pending'] }}</dd></div></dl>
                                <button type="button" data-mobile-report-detail="{{ $rowIndex }}">Lihat Detail<i data-lucide="arrow-right" aria-hidden="true"></i></button>
                            </article>
                        @endforeach
                    </div>
                </div>

                <div class="mobile-report-panel mobile-report-detail" id="mobile-report-panel-detail" role="tabpanel" aria-labelledby="mobile-report-tab-detail" data-mobile-report-panel="detail" hidden>
                    @foreach ($mobileReportRows as $rowIndex => $row)
                        @php [$rowStatusModifier, $rowStatusLabel] = $mobileRowStatus($row['counts']); @endphp
                        <div data-mobile-report-detail-item="{{ $rowIndex }}" @if ($rowIndex !== 0) hidden @endif>
                            <header class="mobile-report-detail__header">
                                <button type="button" data-mobile-report-mode-back aria-label="Kembali ke ringkasan"><i data-lucide="arrow-left" aria-hidden="true"></i></button>
                                <div><p>{{ $row['group']['category']->name }} &middot; {{ $row['group']['frequency_label'] }}</p><h2>{{ $row['item']->item_name }}</h2></div>
                                <span class="mobile-report-badge mobile-report-badge--{{ $rowStatusModifier }}">{{ $rowStatusLabel }}</span>
                            </header>
                            <div class="mobile-report-detail__criteria"><span>Kriteria</span><p>{{ $row['item']->criteria }}</p><small>{{ $report['period']['label'] }}</small></div>
                            <dl class="mobile-report-detail__metrics">
                                <div><dt>Sesuai</dt><dd>{{ $row['counts']['conform'] }}</dd></div>
                                <div><dt>Tidak sesuai</dt><dd>{{ $row['counts']['nonconform'] }}</dd></div>
                                <div><dt>Tidak dijadwalkan</dt><dd>{{ $row['counts']['not_scheduled'] }}</dd></div>
                            </dl>
                            <section aria-label="Tanggal pemeriksaan {{ $row['item']->item_name }}">
                                <div class="mobile-report-section-heading"><div><p>Riwayat Harian</p><h2>Tanggal Pemeriksaan</h2></div></div>
                                <div class="mobile-report-date-grid">
                                    @foreach ($row['cells'] as $day => $cell)
                                        @continue($cell['state'] === 'invalid')
                                        <article class="mobile-report-date-item">
                                            <span class="mobile-report-symbol mobile-report-symbol--{{ $mobileSymbolState($cell['state']) }}" aria-hidden="true">{{ $cell['symbol'] ?: '−' }}</span>
                                            <div><strong>{{ str_pad((string) $day, 2, '0', STR_PAD_LEFT) }} {{ $report['period']['month_name'] }}</strong><small>{{ $cell['label'] }}</small></div>
                                        </article>
                                    @endforeach
                                </div>
                            </section>
                            @foreach ($row['notes'] as $note)<small>{{ $note }}</small>@endforeach
                        </div>
                    @endforeach
                </div>

                <div class="mobile-report-panel mobile-report-table-panel" id="mobile-report-panel-table" role="tabpanel" aria-labelledby="mobile-report-tab-table" data-mobile-report-panel="table" hidden>
                    <div class="mobile-report-table-panel__heading">
                        <div><h2>Format Tanggal 1–31</h2><p>Geser ke samping untuk melihat tanggal lainnya.</p></div>
                        <i data-lucide="move-horizontal" aria-hidden="true"></i>
                    </div>
                    <div class="mobile-report-table-scroll" tabindex="0" aria-label="Tabel laporan bulanan, dapat digeser secara horizontal">
                        <table class="mobile-report-table">
                            <thead><tr><th scope="col">No</th><th scope="col">Kegiatan</th><th scope="col">Kriteria</th>@foreach ($report['days'] as $day)<th scope="col">{{ $day }}</th>@endforeach<th scope="col">Keterangan</th></tr></thead>
                            <tbody>
                                @foreach ($mobileReportRows as $rowIndex => $row)
                                    <tr>
                                        <td>{{ $rowIndex + 1 }}</td>
                                        <td><strong>{{ $row['item']->item_name }}</strong></td>
                                        <td>{{ $row['item']->criteria }}</td>
                                        @foreach ($row['cells'] as $cell)
                                            <td title="{{ $cell['label'] }}">@if ($cell['state'] !== 'invalid')<span class="mobile-report-symbol mobile-report-symbol--{{ $mobileSymbolState($cell['state']) }}" aria-label="{{ $cell['label'] }}">{{ $cell['symbol'] ?: '−' }}</span>@endif</td>
                                        @endforeach
                                        <td>{{ implode('; ', $row['notes']) ?: '-' }}</td>
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
                    <span><i class="mobile-report-symbol mobile-report-symbol--conform" aria-hidden="true">✓</i>Sesuai</span>
                    <span><i class="mobile-report-symbol mobile-report-symbol--nonconform" aria-hidden="true">✕</i>Tidak sesuai</span>
                    <span><i class="mobile-report-symbol mobile-report-symbol--not-scheduled" aria-hidden="true">−</i>Tidak dijadwalkan</span>
                    <span><i class="mobile-report-symbol mobile-report-symbol--pending" aria-hidden="true">—</i>Belum diperiksa</span>
                </div>
            </section>
        @endif

        <section class="mobile-report-export" aria-label="Unduh laporan">
            <a href="{{ route('reports.monthly.pdf', $reportFilters) }}"><i data-lucide="file-down" aria-hidden="true"></i><span><strong>Unduh PDF</strong><small>A3 landscape</small></span></a>
            <a href="{{ route('reports.monthly.excel', $reportFilters) }}"><i data-lucide="sheet" aria-hidden="true"></i><span><strong>Export Excel</strong><small>File .xlsx</small></span></a>
        </section>

        <section class="mobile-report-note">
            <i data-lucide="badge-check" aria-hidden="true"></i>
            <div><strong>Audit Verifikasi</strong>@forelse ($report['verifications'] as $verification)<p>{{ $verification['status_label'] }} &middot; {{ $verification['supervisor'] ?? 'Belum ada supervisor' }} &middot; {{ $verification['verified_at']?->format('d-m-Y H:i') ?? 'Belum diverifikasi' }}</p>@empty<p>Belum ada pemeriksaan final.</p>@endforelse</div>
        </section>
    @endif
</div>
