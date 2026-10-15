@extends('layouts.app')

@section('title', 'Laporan Bulanan')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/monthly-report.css') }}">
@endpush

@section('content')
    <div class="monthly-report-page">
        <header class="monthly-report-header">
            <div class="monthly-report-header__identity">
                <span class="monthly-report-header__icon" aria-hidden="true"><i data-lucide="file-chart-column"></i></span>
                <div>
                    <p class="monthly-report-header__eyebrow">Dokumen Maintenance</p>
                    <h2>Laporan Bulanan</h2>
                    <p>Rekap hasil preventive maintenance mesin X-Ray dari database.</p>
                </div>
            </div>
            <div class="monthly-report-header__actions">
                @if ($report)
                    <a class="report-button report-button--secondary" href="{{ route('reports.monthly.excel', $reportFilters) }}">
                        <i data-lucide="sheet" aria-hidden="true"></i>Export Excel
                    </a>
                    <a class="report-button report-button--primary" href="{{ route('reports.monthly.pdf', $reportFilters) }}">
                        <i data-lucide="download" aria-hidden="true"></i>Unduh PDF
                    </a>
                @endif
            </div>
        </header>

        <section class="report-filter-panel" aria-labelledby="report-filter-title">
            <div class="report-section-heading">
                <span class="report-section-heading__icon" aria-hidden="true"><i data-lucide="list-filter"></i></span>
                <div><h3 id="report-filter-title">Filter Laporan</h3><p>Pilih mesin dan periode laporan yang ingin ditampilkan.</p></div>
            </div>

            @if ($errors->any())
                <div class="report-validation" role="alert">
                    <strong>Filter belum valid.</strong>
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <form class="report-filter" method="GET" action="{{ request()->routeIs('mobile.report') ? route('mobile.report') : route('reports.monthly') }}">
                <label class="report-field report-filter__machine">
                    <span>Mesin</span>
                    <select name="machine_id" required>
                        @forelse ($machines as $machine)
                            <option value="{{ $machine->id }}" @selected($selectedMachine?->is($machine))>
                                {{ $machine->equipment_name }} &middot; {{ $machine->serial_number }}
                            </option>
                        @empty
                            <option value="">Tidak ada mesin yang dapat diakses</option>
                        @endforelse
                    </select>
                </label>
                <label class="report-field">
                    <span>Bulan</span>
                    <select name="month" required>
                        @foreach ($monthOptions as $number => $name)
                            <option value="{{ $number }}" @selected($selectedMonth === $number)>{{ $name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="report-field">
                    <span>Tahun</span>
                    <select name="year" required>
                        @foreach ($yearOptions as $year)
                            <option value="{{ $year }}" @selected($selectedYear === $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="report-filter__actions">
                    <button class="report-button report-button--primary" type="submit"><i data-lucide="file-search" aria-hidden="true"></i>Tampilkan Laporan</button>
                    <a class="report-button report-button--secondary" href="{{ request()->routeIs('mobile.report') ? route('mobile.report') : route('reports.monthly') }}"><i data-lucide="rotate-ccw" aria-hidden="true"></i>Reset</a>
                </div>
            </form>
        </section>

        @if (! $selectedMachine)
            <section class="report-empty-state">
                <i data-lucide="scan-search" aria-hidden="true"></i>
                <h3>Belum ada mesin yang dapat ditampilkan</h3>
                <p>Tambahkan mesin dan jadwal maintenance terlebih dahulu.</p>
            </section>
        @else
            <article class="monthly-report-document" id="monthly-report-document">
                <header class="report-document-heading">
                    <div class="report-document-heading__mark" aria-hidden="true"><i data-lucide="scan-line"></i></div>
                    <div><p>UPBU Malikussaleh</p><h3>Laporan Preventive Maintenance Bulanan</h3><span>Sistem Informasi Preventive Maintenance X-Ray</span></div>
                    <div class="report-document-heading__period"><small>Periode Laporan</small><strong>{{ $report['period']['label'] }}</strong></div>
                </header>

                <section class="report-information" aria-labelledby="report-information-title">
                    <div class="report-information__title"><span aria-hidden="true"><i data-lucide="info"></i></span><h4 id="report-information-title">Informasi Laporan</h4></div>
                    <dl class="report-information__grid">
                        <div><dt>Nama Mesin</dt><dd>{{ $report['machine']->equipment_name }}</dd></div>
                        <div><dt>Merk</dt><dd>{{ $report['machine']->brand }}</dd></div>
                        <div><dt>Tipe</dt><dd>{{ $report['machine']->type }}</dd></div>
                        <div><dt>Serial Number</dt><dd>{{ $report['machine']->serial_number }}</dd></div>
                        <div><dt>Lokasi</dt><dd>{{ $report['machine']->location }}</dd></div>
                        <div><dt>Bandara</dt><dd>{{ $report['machine']->airport }}</dd></div>
                        <div><dt>Periode</dt><dd>{{ $report['period']['label'] }}</dd></div>
                        <div><dt>Petugas</dt><dd>{{ implode(', ', $report['officers']) ?: '-' }}</dd></div>
                    </dl>
                </section>

                <section class="report-table-section" aria-labelledby="monthly-table-title">
                    <div class="report-table-section__heading">
                        <div><h4 id="monthly-table-title">Rekap Pemeriksaan Bulanan</h4><p>Status setiap kegiatan pada periode terpilih.</p></div>
                        <div class="report-legend" aria-label="Keterangan simbol laporan">
                            <span><i class="report-symbol report-symbol--completed" aria-hidden="true">✓</i>Sesuai</span>
                            <span><i class="report-symbol report-symbol--issue" aria-hidden="true">✕</i>Tidak sesuai</span>
                            <span><i class="report-symbol report-symbol--pending" aria-hidden="true">—</i>Belum diperiksa</span>
                            <span><i class="report-symbol report-symbol--not-scheduled" aria-hidden="true">&nbsp;</i>Tidak dijadwalkan</span>
                        </div>
                    </div>

                    @if ($report['groups'] === [])
                        <div class="report-empty-state report-empty-state--inside">
                            <i data-lucide="calendar-x" aria-hidden="true"></i>
                            <h3>Tidak ada jadwal maintenance untuk mesin dan periode yang dipilih.</h3>
                            <p>Pilih periode lain atau buat jadwal maintenance terlebih dahulu.</p>
                        </div>
                    @else
                        <div class="monthly-report-table-scroll">
                            <table class="monthly-report-table">
                                <thead><tr><th>No</th><th>Kegiatan</th><th>Kriteria</th>@foreach ($report['days'] as $day)<th class="monthly-report-table__day">{{ $day }}</th>@endforeach<th>Keterangan</th></tr></thead>
                                <tbody>
                                    @php($sequence = 1)
                                    @foreach ($report['groups'] as $group)
                                        <tr class="monthly-report-table__group"><th colspan="35">{{ $group['category']->name }} &middot; {{ $group['frequency_label'] }}</th></tr>
                                        @foreach ($group['rows'] as $row)
                                            <tr>
                                                <td class="monthly-report-table__number">{{ $sequence++ }}</td>
                                                <td class="monthly-report-table__activity"><strong>{{ $row['item']->item_name }}</strong><small>{{ $group['frequency_label'] }}</small></td>
                                                <td class="monthly-report-table__criteria">{{ $row['item']->criteria }}</td>
                                                @foreach ($row['cells'] as $cell)
                                                    <td class="monthly-report-table__status report-cell--{{ $cell['state'] }}" title="{{ $cell['label'] }}">
                                                        <span @class([
                                                            'report-symbol',
                                                            'report-symbol--completed' => $cell['state'] === 'conform',
                                                            'report-symbol--issue' => $cell['state'] === 'nonconform',
                                                            'report-symbol--pending' => $cell['state'] === 'pending',
                                                            'report-symbol--not-scheduled' => in_array($cell['state'], ['not_scheduled', 'invalid'], true),
                                                        ]) aria-label="{{ $cell['label'] }}">{{ $cell['symbol'] ?: ' ' }}</span>
                                                    </td>
                                                @endforeach
                                                <td class="monthly-report-table__note">@forelse ($row['notes'] as $note)<span>{{ $note }}</span>@empty-@endforelse</td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>

                <section class="monthly-summary" aria-labelledby="monthly-summary-title">
                    <div class="monthly-summary__heading"><div><h4 id="monthly-summary-title">Ringkasan Bulanan</h4><p>Akumulasi data yang sama dengan file export.</p></div><span>{{ $report['period']['label'] }}</span></div>
                    <div class="monthly-summary__grid">
                        <article><span class="monthly-summary__icon monthly-summary__icon--neutral"><i data-lucide="list-checks"></i></span><div><small>Total Item</small><strong>{{ $report['summary']['total_items'] }}</strong></div></article>
                        <article><span class="monthly-summary__icon monthly-summary__icon--completed"><i data-lucide="circle-check"></i></span><div><small>Sesuai</small><strong>{{ $report['summary']['conform'] }}</strong></div></article>
                        <article><span class="monthly-summary__icon monthly-summary__icon--issue"><i data-lucide="circle-x"></i></span><div><small>Tidak Sesuai</small><strong>{{ $report['summary']['nonconform'] }}</strong></div></article>
                        <article><span class="monthly-summary__icon monthly-summary__icon--muted"><i data-lucide="clock"></i></span><div><small>Belum Diperiksa</small><strong>{{ $report['summary']['pending'] }}</strong></div></article>
                        <article><span class="monthly-summary__icon monthly-summary__icon--issue"><i data-lucide="triangle-alert"></i></span><div><small>Total Temuan</small><strong>{{ $report['summary']['findings'] }}</strong></div></article>
                        <article class="monthly-summary__completion"><span class="monthly-summary__icon monthly-summary__icon--completed"><i data-lucide="gauge"></i></span><div><small>Penyelesaian</small><strong>{{ $report['summary']['completion_percentage'] }}%</strong><span class="monthly-summary__progress"><i style="width: {{ $report['summary']['completion_percentage'] }}%"></i></span></div></article>
                    </div>
                </section>

                <section class="report-audit" aria-labelledby="report-audit-title">
                    <div class="report-information__title"><span aria-hidden="true"><i data-lucide="badge-check"></i></span><h4 id="report-audit-title">Audit Verifikasi</h4></div>
                    @forelse ($report['verifications'] as $verification)
                        <article>
                            <strong>{{ $verification['status_label'] }}</strong>
                            <span>{{ $verification['supervisor'] ?? 'Belum ada supervisor' }}</span>
                            <time>{{ $verification['verified_at']?->format('d-m-Y H:i') ?? 'Belum diverifikasi' }}</time>
                            @if ($verification['note'])<p>{{ $verification['note'] }}</p>@endif
                        </article>
                    @empty
                        <p>Belum ada pemeriksaan final untuk diaudit.</p>
                    @endforelse
                    <small>{{ $report['selection_rule'] }}</small>
                </section>
            </article>
        @endif
    </div>
@endsection
