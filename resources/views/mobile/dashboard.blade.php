<div class="mobile-dashboard" data-mobile-dashboard>
    <header class="mobile-dashboard-header">
        <span class="mobile-dashboard-header__icon" aria-hidden="true"><i data-lucide="activity"></i></span>
        <div>
            <p>Halo, {{ $userRole }}</p>
            <h1>Monitoring preventive maintenance hari ini</h1>
            <time datetime="{{ $todayIso }}">{{ $todayLabel }}</time>
        </div>
    </header>

    <section class="mobile-dashboard-section" aria-labelledby="mobile-statistics-title">
        <div class="mobile-dashboard-section__heading">
            <div><p>Ringkasan Operasional</p><h2 id="mobile-statistics-title">Status Hari Ini</h2></div>
            <span>Update {{ $updatedAtLabel }}</span>
        </div>
        @php
            $mobileStats = [
                ['class' => 'today', 'icon' => 'scan-line', 'title' => 'Total Mesin', 'value' => $statistics['total_machines'], 'description' => 'Mesin dalam cakupan'],
                ['class' => 'today', 'icon' => 'clipboard-list', 'title' => 'Pemeriksaan Hari Ini', 'value' => $statistics['today_schedules'], 'description' => 'Jadwal aktif hari ini'],
                ['class' => 'complete', 'icon' => 'circle-check-big', 'title' => 'Selesai', 'value' => $statistics['completed_today'], 'description' => $dailyProgress['percentage'].'% sudah diperiksa'],
                ['class' => 'pending', 'icon' => 'clock-3', 'title' => 'Belum Dikerjakan', 'value' => $statistics['pending_today'], 'description' => 'Menunggu pemeriksaan'],
                ['class' => 'late', 'icon' => 'timer-off', 'title' => 'Terlambat', 'value' => $statistics['overdue'], 'description' => 'Melewati jadwal'],
                ['class' => 'finding', 'icon' => 'triangle-alert', 'title' => 'Temuan Open', 'value' => $statistics['findings_open'], 'description' => 'Perlu tindak lanjut'],
                ['class' => 'pending', 'icon' => 'search-check', 'title' => 'Temuan Review', 'value' => $statistics['findings_review'], 'description' => 'Sedang ditinjau'],
                ['class' => 'complete', 'icon' => 'shield-check', 'title' => 'Temuan Resolved', 'value' => $statistics['findings_resolved'], 'description' => 'Sudah diselesaikan'],
                ['class' => 'pending', 'icon' => 'badge-check', 'title' => 'Menunggu Verifikasi', 'value' => $statistics['waiting_verification'], 'description' => 'Perlu ditinjau supervisor'],
            ];
        @endphp
        <div class="mobile-statistics">
            @foreach ($mobileStats as $stat)
                <article class="mobile-statistic mobile-statistic--{{ $stat['class'] }}" data-mobile-stat-card>
                    <span class="mobile-statistic__icon" aria-hidden="true"><i data-lucide="{{ $stat['icon'] }}"></i></span>
                    <div><small>{{ $stat['title'] }}</small><strong>{{ $stat['value'] }}</strong><p>{{ $stat['description'] }}</p></div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="mobile-dashboard-card mobile-progress-card" aria-labelledby="mobile-progress-title">
        <div class="mobile-dashboard-card__heading">
            <div><p>Ringkasan pemeriksaan</p><h2 id="mobile-progress-title">Progress Hari Ini</h2></div>
            <strong>{{ $dailyProgress['percentage'] }}%</strong>
        </div>
        <div class="mobile-progress-track" role="progressbar" aria-label="Progress pemeriksaan hari ini" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $dailyProgress['percentage'] }}">
            <i style="width: {{ $dailyProgress['percentage'] }}%"></i>
        </div>
        <div class="mobile-progress-card__footer">
            <span><strong>{{ $dailyProgress['completed'] }}</strong> dari {{ $dailyProgress['total'] }} pemeriksaan selesai</span>
            <button type="button" data-mobile-dashboard-action="inspection" data-url="{{ route('mobile.inspection-today') }}">Lihat Pemeriksaan<i data-lucide="arrow-right" aria-hidden="true"></i></button>
        </div>
    </section>

    <section class="mobile-dashboard-section" aria-labelledby="mobile-schedules-title">
        <div class="mobile-dashboard-section__heading">
            <div><p>Operasional lapangan</p><h2 id="mobile-schedules-title">Jadwal Hari Ini</h2></div>
            <span>{{ $todaySchedules->count() }} jadwal</span>
        </div>
        <div class="mobile-schedule-list">
            @forelse ($todaySchedules as $schedule)
                @php
                    $mobileScheduleBadge = match ($schedule->status) {
                        'completed' => 'complete',
                        'pending', 'in_progress' => 'pending',
                        'overdue' => 'open',
                        default => 'scheduled',
                    };
                    $mobileScheduleRoute = auth()->user()->role->value === 'petugas'
                        ? route('mobile.inspection-today', ['schedule' => $schedule->id])
                        : route('schedules.show', ['schedule' => $schedule, 'context' => 'mobile']);
                @endphp
                <article class="mobile-schedule-item" data-mobile-schedule-item>
                    <span class="mobile-schedule-item__time"><strong>{{ substr((string) $schedule->scheduled_time, 0, 5) }}</strong><small>WITA</small></span>
                    <div class="mobile-schedule-item__content">
                        <h3>{{ $schedule->machine->equipment_name }}</h3>
                        <p><i data-lucide="map-pin" aria-hidden="true"></i>{{ $schedule->machine->location }}</p>
                        <span class="mobile-dashboard-badge mobile-dashboard-badge--{{ $mobileScheduleBadge }}">{{ $scheduleStatusLabels[$schedule->status] ?? ucfirst($schedule->status) }}</span>
                    </div>
                    <button type="button" data-mobile-dashboard-action="schedule-detail" data-url="{{ $mobileScheduleRoute }}">Lihat</button>
                </article>
            @empty
                <div class="mobile-dashboard-empty" data-mobile-empty-state="schedules">
                    <i data-lucide="calendar-x" aria-hidden="true"></i><strong>Belum ada jadwal</strong><p>Belum ada jadwal pemeriksaan hari ini.</p>
                </div>
            @endforelse
        </div>
    </section>

    <section class="mobile-dashboard-section" aria-labelledby="mobile-findings-title">
        <div class="mobile-dashboard-section__heading">
            <div><p>Monitoring tindak lanjut</p><h2 id="mobile-findings-title">Temuan Terbaru</h2></div>
            <button type="button" data-mobile-dashboard-action="findings" data-url="{{ route('mobile.findings') }}">Lihat Semua</button>
        </div>
        <div class="mobile-finding-list">
            @forelse ($latestFindings as $finding)
                <article class="mobile-finding-item" data-mobile-finding-item>
                    <span class="mobile-finding-item__icon{{ $finding->status === 'resolved' ? ' mobile-finding-item__icon--resolved' : '' }}" aria-hidden="true"><i data-lucide="{{ $finding->status === 'resolved' ? 'circle-check' : 'triangle-alert' }}"></i></span>
                    <div>
                        <h3>{{ $finding->item?->item_name ?? 'Item pemeriksaan' }}</h3>
                        <p>{{ $finding->machine->equipment_name }} &bull; {{ $finding->finding_date->locale('id')->translatedFormat('d M Y') }}</p>
                    </div>
                    <span class="mobile-dashboard-badge mobile-dashboard-badge--{{ $finding->status }}">{{ $findingStatusLabels[$finding->status] ?? ucfirst($finding->status) }}</span>
                </article>
            @empty
                <div class="mobile-dashboard-empty" data-mobile-empty-state="findings">
                    <i data-lucide="shield-check" aria-hidden="true"></i><strong>Belum ada temuan</strong><p>Belum ada temuan terbaru.</p>
                </div>
            @endforelse
        </div>
    </section>

    <section class="mobile-dashboard-card mobile-monthly-preview" data-mobile-monthly-preview aria-labelledby="mobile-monthly-title">
        <div class="mobile-dashboard-card__heading">
            <div><p>{{ $monthLabel }}</p><h2 id="mobile-monthly-title">Preview Laporan Bulanan</h2></div>
            <span class="mobile-monthly-preview__icon" aria-hidden="true"><i data-lucide="file-chart-column"></i></span>
        </div>
        <div class="mobile-monthly-preview__metrics">
            <div><small>Total Pemeriksaan</small><strong>{{ $monthlySummary['total_checks'] }}</strong></div>
            <div><small>Item Sesuai</small><strong>{{ $monthlySummary['compliant_details'] }}</strong></div>
            <div><small>Tidak Sesuai</small><strong>{{ $monthlySummary['noncompliant_details'] }}</strong></div>
            <div><small>Total Temuan</small><strong>{{ $monthlySummary['total_findings'] }}</strong></div>
            <div><small>Terverifikasi</small><strong>{{ $monthlySummary['verified_checks'] }}</strong></div>
            <div><small>Menunggu Verifikasi</small><strong>{{ $monthlySummary['waiting_verification'] }}</strong></div>
        </div>
        <div class="mobile-monthly-preview__completion"><span><strong>{{ $monthlySummary['completion_percentage'] }}%</strong> penyelesaian</span><div><i style="width: {{ $monthlySummary['completion_percentage'] }}%"></i></div></div>
        <button class="mobile-dashboard-button mobile-dashboard-button--secondary" type="button" data-mobile-dashboard-action="report" data-url="{{ route('mobile.report') }}">Lihat Laporan<i data-lucide="arrow-right" aria-hidden="true"></i></button>
    </section>

    <section class="mobile-dashboard-section" aria-labelledby="mobile-quick-actions-title">
        <div class="mobile-dashboard-section__heading"><div><p>Akses utama</p><h2 id="mobile-quick-actions-title">Aksi Cepat</h2></div></div>
        <div class="mobile-quick-actions">
            <button type="button" data-mobile-dashboard-action="inspection" data-url="{{ route('mobile.inspection-today') }}"><span><i data-lucide="clipboard-check" aria-hidden="true"></i></span><strong>Mulai Pemeriksaan</strong><small>Checklist hari ini</small></button>
            <button type="button" data-mobile-dashboard-action="schedules" data-url="{{ route('mobile.schedules') }}"><span><i data-lucide="calendar-clock" aria-hidden="true"></i></span><strong>Lihat Jadwal</strong><small>Agenda maintenance</small></button>
            <button type="button" data-mobile-dashboard-action="findings" data-url="{{ route('mobile.findings') }}"><span><i data-lucide="triangle-alert" aria-hidden="true"></i></span><strong>Lihat Temuan</strong><small>Tindak lanjut</small></button>
            @if ($userRole !== 'Petugas')
                <button type="button" data-mobile-dashboard-action="report" data-url="{{ route('mobile.report') }}"><span><i data-lucide="file-chart-column" aria-hidden="true"></i></span><strong>Laporan Bulanan</strong><small>Rekap {{ $monthLabel }}</small></button>
            @else
                <button type="button" data-mobile-dashboard-action="history" data-url="{{ route('mobile.history') }}"><span><i data-lucide="history" aria-hidden="true"></i></span><strong>Riwayat</strong><small>Pemeriksaan terdahulu</small></button>
            @endif
        </div>
    </section>

    <div class="mobile-dashboard-toast" data-mobile-dashboard-toast role="status" aria-live="polite" hidden>
        <i data-lucide="circle-check" aria-hidden="true"></i><span></span>
    </div>
</div>
