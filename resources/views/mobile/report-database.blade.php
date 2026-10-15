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
            <section class="mobile-report-view" aria-labelledby="mobile-report-items-title">
                <div class="mobile-report-section-heading"><div><p>Hasil Per Kegiatan</p><h2 id="mobile-report-items-title">Item Pemeriksaan</h2></div><span>{{ $report['summary']['total_items'] }} item</span></div>
                <div class="mobile-report-activity-list">
                    @php($sequence = 1)
                    @foreach ($report['groups'] as $group)
                        @foreach ($group['rows'] as $row)
                            @php($rowCells = collect($row['cells']))
                            @php($rowConform = $rowCells->where('state', 'conform')->count())
                            @php($rowNonconform = $rowCells->where('state', 'nonconform')->count())
                            @php($rowPending = $rowCells->where('state', 'pending')->count())
                            <article class="mobile-report-activity-card">
                                <header><span aria-hidden="true">{{ str_pad((string) $sequence++, 2, '0', STR_PAD_LEFT) }}</span><div><h3>{{ $row['item']->item_name }}</h3><p>{{ $row['item']->criteria }}</p></div><span class="mobile-report-badge {{ $rowNonconform > 0 ? 'mobile-report-badge--finding' : 'mobile-report-badge--normal' }}">{{ $rowNonconform > 0 ? 'Ada Temuan' : 'Normal' }}</span></header>
                                <p>{{ $group['category']->name }} &middot; {{ $group['frequency_label'] }}</p>
                                <dl><div><dt>Sesuai</dt><dd>{{ $rowConform }}</dd></div><div><dt>Tidak sesuai</dt><dd>{{ $rowNonconform }}</dd></div><div><dt>Belum diperiksa</dt><dd>{{ $rowPending }}</dd></div></dl>
                                @foreach ($row['notes'] as $note)<small>{{ $note }}</small>@endforeach
                            </article>
                        @endforeach
                    @endforeach
                </div>
            </section>

            <section class="mobile-report-panel">
                <div class="mobile-report-section-heading"><div><p>Tabel Manual</p><h2>Rekap Tanggal 1–31</h2></div></div>
                <div class="mobile-report-table-scroll">
                    <table class="mobile-report-table mobile-report-table--database">
                        <thead><tr><th>Kegiatan</th>@foreach ($report['days'] as $day)<th>{{ $day }}</th>@endforeach<th>Ket</th></tr></thead>
                        <tbody>
                            @foreach ($report['groups'] as $group)
                                @foreach ($group['rows'] as $row)
                                    <tr><td><strong>{{ $row['item']->item_name }}</strong><small>{{ $group['frequency_label'] }}</small></td>@foreach ($row['cells'] as $cell)<td class="mobile-report-cell--{{ $cell['state'] }}" title="{{ $cell['label'] }}">{{ $cell['symbol'] ?: ' ' }}</td>@endforeach<td>{{ implode('; ', $row['notes']) ?: '-' }}</td></tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
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
