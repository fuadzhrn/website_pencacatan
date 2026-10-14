@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/dashboard.css') }}">
@endpush

@section('content')
    <div class="dashboard-page">
        <section class="dashboard-stats" aria-label="Ringkasan preventive maintenance">
            @php
                $statCards = [
                    ['class' => 'scheduled', 'icon' => 'scan-line', 'label' => 'Aset', 'title' => 'Total Mesin', 'value' => $statistics['total_machines'], 'description' => 'Mesin dalam cakupan monitoring'],
                    ['class' => 'scheduled', 'icon' => 'clipboard-list', 'label' => 'Hari ini', 'title' => 'Pemeriksaan Hari Ini', 'value' => $statistics['today_schedules'], 'description' => 'Jadwal pemeriksaan aktif'],
                    ['class' => 'complete', 'icon' => 'circle-check-big', 'label' => 'Selesai', 'title' => 'Selesai Hari Ini', 'value' => $statistics['completed_today'], 'description' => $dailyProgress['percentage'].'% jadwal selesai'],
                    ['class' => 'pending', 'icon' => 'clock-3', 'label' => 'Tertunda', 'title' => 'Belum Dikerjakan', 'value' => $statistics['pending_today'], 'description' => 'Menunggu pemeriksaan'],
                    ['class' => 'overdue', 'icon' => 'timer-off', 'label' => 'Overdue', 'title' => 'Terlambat', 'value' => $statistics['overdue'], 'description' => 'Melewati jadwal pemeriksaan'],
                    ['class' => 'finding', 'icon' => 'triangle-alert', 'label' => 'Temuan', 'title' => 'Temuan Open', 'value' => $statistics['findings_open'], 'description' => 'Perlu tindak lanjut'],
                    ['class' => 'pending', 'icon' => 'search-check', 'label' => 'Review', 'title' => 'Temuan Review', 'value' => $statistics['findings_review'], 'description' => 'Sedang ditinjau'],
                    ['class' => 'complete', 'icon' => 'shield-check', 'label' => 'Resolved', 'title' => 'Temuan Selesai', 'value' => $statistics['findings_resolved'], 'description' => 'Sudah diselesaikan'],
                    ['class' => 'pending', 'icon' => 'badge-check', 'label' => 'Verifikasi', 'title' => 'Menunggu Verifikasi', 'value' => $statistics['waiting_verification'], 'description' => 'Pemeriksaan perlu ditinjau'],
                ];
            @endphp

            @foreach ($statCards as $card)
                <article class="dashboard-stat dashboard-stat--{{ $card['class'] }}">
                    <div class="dashboard-stat__header">
                        <span class="dashboard-stat__icon" aria-hidden="true"><i data-lucide="{{ $card['icon'] }}"></i></span>
                        <span class="dashboard-stat__label">{{ $card['label'] }}</span>
                    </div>
                    <div class="dashboard-stat__body">
                        <p>{{ $card['title'] }}</p>
                        <strong>{{ $card['value'] }}</strong>
                        <span>{{ $card['description'] }}</span>
                    </div>
                </article>
            @endforeach
        </section>

        <div class="dashboard-primary-grid">
            <section class="dashboard-panel dashboard-panel--inspection" aria-labelledby="today-inspection-title">
                <header class="dashboard-panel__header">
                    <div class="dashboard-panel__title">
                        <span class="dashboard-panel__icon" aria-hidden="true"><i data-lucide="calendar-check-2"></i></span>
                        <div><p>{{ $todayLabel }}</p><h2 id="today-inspection-title">Pemeriksaan Hari Ini</h2></div>
                    </div>
                    <a class="button button--secondary" href="{{ route('inspections.today') }}">
                        <i data-lucide="arrow-right" aria-hidden="true"></i> Lihat Semua
                    </a>
                </header>

                <div class="dashboard-table-wrap">
                    <table class="dashboard-checklist-table">
                        <thead>
                            <tr>
                                <th scope="col">Mesin / Lokasi</th>
                                <th scope="col">Frekuensi / Petugas</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($todaySchedules as $schedule)
                                @php
                                    $scheduleBadge = match ($schedule->status) {
                                        'completed' => 'success',
                                        'pending' => 'pending',
                                        'in_progress' => 'verified',
                                        'overdue' => 'overdue',
                                        default => 'neutral',
                                    };
                                    $detailRoute = auth()->user()->role->value === 'petugas'
                                        ? route('inspections.schedules.show', $schedule)
                                        : route('schedules.show', $schedule);
                                @endphp
                                <tr>
                                    <td>
                                        <span class="dashboard-item-index">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                        <a href="{{ $detailRoute }}">{{ $schedule->machine->equipment_name }}</a><br>
                                        <small>{{ $schedule->machine->location }}</small>
                                    </td>
                                    <td>
                                        {{ $frequencyLabels[$schedule->maintenanceCategory->frequency] ?? ucfirst($schedule->maintenanceCategory->frequency) }}<br>
                                        <small>{{ $schedule->assignedUser?->name ?? 'Belum ditugaskan' }}</small>
                                    </td>
                                    <td>
                                        <span class="status-badge status-badge--{{ $scheduleBadge }}">
                                            {{ $scheduleStatusLabels[$schedule->status] ?? ucfirst($schedule->status) }}
                                        </span>
                                        @if ($schedule->check)
                                            <small>{{ $checkStatusLabels[$schedule->check->overall_status] ?? ucfirst($schedule->check->overall_status) }}</small>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3">Belum ada jadwal pemeriksaan hari ini</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <footer class="dashboard-panel__footer">
                    <span><strong>{{ $dailyProgress['completed'] }}</strong> dari {{ $dailyProgress['total'] }} jadwal selesai</span>
                    <strong>{{ $dailyProgress['percentage'] }}%</strong>
                </footer>
            </section>

            <section class="dashboard-panel dashboard-panel--findings" aria-labelledby="latest-findings-title">
                <header class="dashboard-panel__header">
                    <div class="dashboard-panel__title">
                        <span class="dashboard-panel__icon dashboard-panel__icon--danger" aria-hidden="true"><i data-lucide="triangle-alert"></i></span>
                        <div><p>Monitoring tindak lanjut</p><h2 id="latest-findings-title">Temuan Terbaru</h2></div>
                    </div>
                </header>
                <div class="dashboard-findings-list">
                    @forelse ($latestFindings as $finding)
                        <article class="dashboard-finding">
                            <span class="dashboard-finding__marker dashboard-finding__marker--{{ $finding->status === 'resolved' ? 'normal' : $finding->status }}" aria-hidden="true"></span>
                            <div class="dashboard-finding__content">
                                <div>
                                    <h3>{{ $finding->item?->item_name ?? 'Item pemeriksaan' }}</h3>
                                    <span class="status-badge status-badge--{{ $finding->status === 'resolved' ? 'success' : ($finding->status === 'review' ? 'pending' : 'danger') }}">
                                        {{ $findingStatusLabels[$finding->status] ?? ucfirst($finding->status) }}
                                    </span>
                                </div>
                                <p>{{ \Illuminate\Support\Str::limit($finding->finding_description, 90) }}</p>
                                <small><i data-lucide="scan-line" aria-hidden="true"></i>{{ $finding->machine->equipment_name }} &bull; {{ $finding->finding_date->locale('id')->translatedFormat('d M Y') }}</small>
                            </div>
                        </article>
                    @empty
                        <article class="dashboard-finding"><div class="dashboard-finding__content"><h3>Belum ada temuan terbaru</h3><p>Temuan pemeriksaan akan tampil di sini.</p></div></article>
                    @endforelse
                </div>
                <a class="button button--secondary dashboard-findings-action" href="{{ route('findings.index') }}">Lihat Semua Temuan</a>
            </section>
        </div>

        <section class="dashboard-panel dashboard-report" aria-labelledby="monthly-report-title">
            <header class="dashboard-panel__header">
                <div class="dashboard-panel__title">
                    <span class="dashboard-panel__icon" aria-hidden="true"><i data-lucide="file-chart-column"></i></span>
                    <div><p>{{ $monthLabel }}</p><h2 id="monthly-report-title">Ringkasan Laporan Bulanan</h2></div>
                </div>
                @if ($userRole !== 'Petugas')
                    <a class="button button--primary" href="{{ route('reports.monthly') }}"><i data-lucide="file-text" aria-hidden="true"></i> Lihat Laporan</a>
                @endif
            </header>
            <div class="dashboard-report__table-wrap">
                <table class="dashboard-report-table">
                    <thead>
                        <tr>
                            <th scope="col">Total Pemeriksaan</th>
                            <th scope="col">Item Sesuai</th>
                            <th scope="col">Tidak Sesuai</th>
                            <th scope="col">Total Temuan</th>
                            <th scope="col">Penyelesaian</th>
                            <th scope="col">Terverifikasi</th>
                            <th scope="col">Menunggu Verifikasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $monthlySummary['total_checks'] }}</td>
                            <td>{{ $monthlySummary['compliant_details'] }}</td>
                            <td>{{ $monthlySummary['noncompliant_details'] }}</td>
                            <td>{{ $monthlySummary['total_findings'] }}</td>
                            <td>{{ $monthlySummary['completion_percentage'] }}%</td>
                            <td>{{ $monthlySummary['verified_checks'] }}</td>
                            <td>{{ $monthlySummary['waiting_verification'] }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

@endsection
