<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Maintenance {{ $report['period']['label'] }}</title>
    <style>
        @page { size: A3 landscape; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111827; font-family: "DejaVu Sans", sans-serif; font-size: 7px; }
        h1, h2, p { margin: 0; }
        .heading { border-bottom: 2px solid #047857; margin-bottom: 8px; padding-bottom: 7px; text-align: center; }
        .heading h1 { font-size: 15px; letter-spacing: .3px; }
        .heading p { color: #4b5563; font-size: 8px; margin-top: 3px; }
        .identity { border-collapse: collapse; margin-bottom: 8px; width: 100%; }
        .identity th, .identity td { border: 1px solid #d1d5db; padding: 4px 5px; text-align: left; }
        .identity th { background: #f3f4f6; width: 9%; }
        .identity td { width: 24%; }
        .legend { margin: 6px 0; }
        .legend span { display: inline-block; margin-right: 14px; }
        .legend strong { display: inline-block; min-width: 13px; text-align: center; }
        .matrix { border-collapse: collapse; table-layout: fixed; width: 100%; }
        .matrix thead { display: table-header-group; }
        .matrix tr { page-break-inside: avoid; }
        .matrix th, .matrix td { border: 1px solid #9ca3af; padding: 2px; vertical-align: top; word-wrap: break-word; }
        .matrix th { background: #1f2933; color: #fff; font-size: 6px; text-align: center; }
        .matrix .no { width: 17px; text-align: center; }
        .matrix .category { width: 65px; }
        .matrix .frequency { width: 42px; text-align: center; }
        .matrix .activity { width: 92px; }
        .matrix .criteria { width: 125px; }
        .matrix .day { font-size: 7px; text-align: center; width: 15px; }
        .matrix .note { font-size: 5.5px; width: 180px; }
        .matrix .category-start td { border-top: 1.5px solid #1f2933; }
        .state-conform { background: #d1fae5; color: #047857; font-weight: bold; }
        .state-nonconform { background: #fee2e2; color: #dc2626; font-weight: bold; }
        .state-pending { background: #fef3c7; color: #92400e; font-weight: bold; }
        .state-not_scheduled, .state-invalid { background: #f3f4f6; color: #9ca3af; }
        .empty { border: 1px solid #d1d5db; color: #6b7280; padding: 16px; text-align: center; }
        .summary { border-collapse: collapse; margin-top: 8px; width: 100%; }
        .summary th, .summary td { border: 1px solid #d1d5db; padding: 4px; text-align: center; }
        .summary th { background: #f3f4f6; }
        .audit { margin-top: 10px; page-break-inside: avoid; }
        .audit h2 { font-size: 9px; margin-bottom: 4px; }
        .audit table { border-collapse: collapse; width: 100%; }
        .audit th, .audit td { border: 1px solid #d1d5db; padding: 4px; text-align: left; }
        .audit th { background: #f3f4f6; }
        .rule { color: #4b5563; font-size: 6px; margin-top: 7px; }
    </style>
</head>
<body>
    <header class="heading">
        <h1>LAPORAN PREVENTIVE MAINTENANCE MESIN X-RAY</h1>
        <p>UPBU Malikussaleh &middot; Periode {{ $report['period']['label'] }}</p>
    </header>

    <table class="identity">
        <tr>
            <th>Nama Peralatan</th><td>{{ $report['machine']->equipment_name }}</td>
            <th>Merk</th><td>{{ $report['machine']->brand }}</td>
            <th>Tipe</th><td>{{ $report['machine']->type }}</td>
        </tr>
        <tr>
            <th>Serial Number</th><td>{{ $report['machine']->serial_number }}</td>
            <th>Lokasi</th><td>{{ $report['machine']->location }}</td>
            <th>Bandara</th><td>{{ $report['machine']->airport }}</td>
        </tr>
    </table>

    <div class="legend">
        <span><strong style="color:#047857">✓</strong> Sesuai</span>
        <span><strong style="color:#dc2626">✕</strong> Tidak sesuai</span>
        <span><strong style="color:#92400e">—</strong> Dijadwalkan, belum ada hasil final</span>
        <span><strong style="background:#f3f4f6">&nbsp;</strong> Tidak dijadwalkan/tanggal tidak tersedia</span>
    </div>

    @if ($report['groups'] === [])
        <div class="empty">Tidak ada jadwal maintenance untuk mesin dan periode yang dipilih.</div>
    @else
        <table class="matrix">
            <thead>
                <tr>
                    <th class="no">No</th>
                    <th class="category">Kategori</th>
                    <th class="frequency">Frekuensi</th>
                    <th class="activity">Kegiatan</th>
                    <th class="criteria">Kriteria</th>
                    @foreach ($report['days'] as $day)
                        <th class="day">{{ $day }}</th>
                    @endforeach
                    <th class="note">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @php($sequence = 1)
                @foreach ($report['groups'] as $group)
                    @foreach ($group['rows'] as $row)
                        <tr @class(['category-start' => $loop->first])>
                            <td class="no">{{ $sequence++ }}</td>
                            <td>{{ $group['category']->name }}</td>
                            <td style="text-align:center">{{ $group['frequency_label'] }}</td>
                            <td>{{ $row['item']->item_name }}</td>
                            <td>{{ $row['item']->criteria }}</td>
                            @foreach ($row['cells'] as $cell)
                                <td class="day state-{{ $cell['state'] }}" title="{{ $cell['label'] }}">{{ $cell['symbol'] }}</td>
                            @endforeach
                            <td class="note">
                                @forelse ($row['notes'] as $note)
                                    <div>{{ $note }}</div>
                                @empty
                                    -
                                @endforelse
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endif

    <table class="summary">
        <tr>
            <th>Total Item</th><th>Sel Terjadwal</th><th>Sesuai</th><th>Tidak Sesuai</th><th>Belum Diperiksa</th><th>Temuan</th><th>Penyelesaian</th>
        </tr>
        <tr>
            <td>{{ $report['summary']['total_items'] }}</td>
            <td>{{ $report['summary']['scheduled'] }}</td>
            <td>{{ $report['summary']['conform'] }}</td>
            <td>{{ $report['summary']['nonconform'] }}</td>
            <td>{{ $report['summary']['pending'] }}</td>
            <td>{{ $report['summary']['findings'] }}</td>
            <td>{{ $report['summary']['completion_percentage'] }}%</td>
        </tr>
    </table>

    <section class="audit">
        <h2>Audit Verifikasi</h2>
        @if ($report['verifications'] === [])
            <p>Belum ada pemeriksaan final pada periode ini.</p>
        @else
            <table>
                <thead><tr><th>Status</th><th>Supervisor</th><th>Waktu Verifikasi</th><th>Catatan</th></tr></thead>
                <tbody>
                    @foreach ($report['verifications'] as $verification)
                        <tr>
                            <td>{{ $verification['status_label'] }}</td>
                            <td>{{ $verification['supervisor'] ?? '-' }}</td>
                            <td>{{ $verification['verified_at']?->format('d-m-Y H:i') ?? '-' }}</td>
                            <td>{{ $verification['note'] ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <p class="rule">Aturan pemilihan data: {{ $report['selection_rule'] }}</p>
</body>
</html>
