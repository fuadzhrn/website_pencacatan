<div class="mobile-dashboard" data-mobile-dashboard>
    <header class="mobile-dashboard-header">
        <span class="mobile-dashboard-header__icon" aria-hidden="true"><i data-lucide="activity"></i></span>
        <div>
            <p>Halo, {{ $userRole }}</p>
            <h1>Monitoring preventive maintenance hari ini</h1>
            <time datetime="2026-10-09">Jumat, 09 Oktober 2026</time>
        </div>
    </header>

    <section class="mobile-dashboard-section" aria-labelledby="mobile-statistics-title">
        <div class="mobile-dashboard-section__heading">
            <div><p>Ringkasan Operasional</p><h2 id="mobile-statistics-title">Status Hari Ini</h2></div>
            <span>Update 08.30 WITA</span>
        </div>
        <div class="mobile-statistics">
            <article class="mobile-statistic mobile-statistic--today" data-mobile-stat-card>
                <span class="mobile-statistic__icon" aria-hidden="true"><i data-lucide="clipboard-list"></i></span>
                <div><small>Pemeriksaan Hari Ini</small><strong>12</strong><p>5 item terjadwal berikutnya</p></div>
            </article>
            <article class="mobile-statistic mobile-statistic--complete" data-mobile-stat-card>
                <span class="mobile-statistic__icon" aria-hidden="true"><i data-lucide="circle-check-big"></i></span>
                <div><small>Selesai</small><strong>8</strong><p>67% sudah diperiksa</p></div>
            </article>
            <article class="mobile-statistic mobile-statistic--pending" data-mobile-stat-card>
                <span class="mobile-statistic__icon" aria-hidden="true"><i data-lucide="clock-3"></i></span>
                <div><small>Belum Dikerjakan</small><strong>3</strong><p>Menunggu pemeriksaan</p></div>
            </article>
            <article class="mobile-statistic mobile-statistic--finding" data-mobile-stat-card>
                <span class="mobile-statistic__icon" aria-hidden="true"><i data-lucide="triangle-alert"></i></span>
                <div><small>Temuan</small><strong>1</strong><p>Perlu tindak lanjut</p></div>
            </article>
            <article class="mobile-statistic mobile-statistic--late" data-mobile-stat-card>
                <span class="mobile-statistic__icon" aria-hidden="true"><i data-lucide="timer-off"></i></span>
                <div><small>Terlambat</small><strong>2</strong><p>Melewati jadwal</p></div>
            </article>
        </div>
    </section>

    <section class="mobile-dashboard-card mobile-progress-card" aria-labelledby="mobile-progress-title">
        <div class="mobile-dashboard-card__heading">
            <div><p>Ringkasan pemeriksaan</p><h2 id="mobile-progress-title">Progress Hari Ini</h2></div>
            <strong>67%</strong>
        </div>
        <div class="mobile-progress-track" role="progressbar" aria-label="Progress pemeriksaan hari ini" aria-valuemin="0" aria-valuemax="100" aria-valuenow="67"><i></i></div>
        <div class="mobile-progress-card__footer">
            <span><strong>8</strong> dari 12 pemeriksaan selesai</span>
            <button type="button" data-mobile-dashboard-action="inspection">Lihat Pemeriksaan<i data-lucide="arrow-right" aria-hidden="true"></i></button>
        </div>
    </section>

    <section class="mobile-dashboard-section" aria-labelledby="mobile-schedules-title">
        <div class="mobile-dashboard-section__heading">
            <div><p>Operasional lapangan</p><h2 id="mobile-schedules-title">Jadwal Hari Ini</h2></div>
            <span>3 jadwal</span>
        </div>
        <div class="mobile-schedule-list">
            <article class="mobile-schedule-item" data-mobile-schedule-item>
                <span class="mobile-schedule-item__time"><strong>08:00</strong><small>WITA</small></span>
                <div class="mobile-schedule-item__content"><h3>X-Ray Baggage HI-Scan 100100T</h3><p><i data-lucide="map-pin" aria-hidden="true"></i>Terminal 1</p><span class="mobile-dashboard-badge mobile-dashboard-badge--complete">Selesai</span></div>
                <button type="button" data-mobile-dashboard-action="schedule-detail" data-feedback="Membuka detail jadwal X-Ray Baggage.">Lihat</button>
            </article>
            <article class="mobile-schedule-item" data-mobile-schedule-item>
                <span class="mobile-schedule-item__time"><strong>09:30</strong><small>WITA</small></span>
                <div class="mobile-schedule-item__content"><h3>X-Ray Cabin</h3><p><i data-lucide="map-pin" aria-hidden="true"></i>Terminal 2</p><span class="mobile-dashboard-badge mobile-dashboard-badge--pending">Belum Dikerjakan</span></div>
                <button type="button" data-mobile-dashboard-action="schedule-detail" data-feedback="Membuka detail jadwal X-Ray Cabin.">Lihat</button>
            </article>
            <article class="mobile-schedule-item" data-mobile-schedule-item>
                <span class="mobile-schedule-item__time"><strong>13:00</strong><small>WITA</small></span>
                <div class="mobile-schedule-item__content"><h3>X-Ray Cargo</h3><p><i data-lucide="map-pin" aria-hidden="true"></i>Area Cargo</p><span class="mobile-dashboard-badge mobile-dashboard-badge--scheduled">Terjadwal</span></div>
                <button type="button" data-mobile-dashboard-action="schedule-detail" data-feedback="Membuka detail jadwal X-Ray Cargo.">Lihat</button>
            </article>
        </div>
        <div class="mobile-dashboard-empty" data-mobile-empty-state="schedules" hidden>
            <i data-lucide="calendar-x" aria-hidden="true"></i><strong>Belum ada jadwal</strong><p>Belum ada jadwal pemeriksaan hari ini.</p>
        </div>
    </section>

    <section class="mobile-dashboard-section" aria-labelledby="mobile-findings-title">
        <div class="mobile-dashboard-section__heading">
            <div><p>Monitoring tindak lanjut</p><h2 id="mobile-findings-title">Temuan Terbaru</h2></div>
            <button type="button" data-mobile-dashboard-action="findings">Lihat Semua</button>
        </div>
        <div class="mobile-finding-list">
            <article class="mobile-finding-item" data-mobile-finding-item>
                <span class="mobile-finding-item__icon" aria-hidden="true"><i data-lucide="lightbulb-off"></i></span>
                <div><h3>Indicator lamp tidak menyala</h3><p>X-Ray Cabin &bull; Hari ini</p></div>
                <span class="mobile-dashboard-badge mobile-dashboard-badge--review">Review</span>
            </article>
            <article class="mobile-finding-item" data-mobile-finding-item>
                <span class="mobile-finding-item__icon" aria-hidden="true"><i data-lucide="battery-warning"></i></span>
                <div><h3>UPS tidak normal</h3><p>X-Ray Cargo &bull; Kemarin</p></div>
                <span class="mobile-dashboard-badge mobile-dashboard-badge--open">Open</span>
            </article>
            <article class="mobile-finding-item" data-mobile-finding-item>
                <span class="mobile-finding-item__icon mobile-finding-item__icon--resolved" aria-hidden="true"><i data-lucide="circle-check"></i></span>
                <div><h3>Conveyor belt tidak stabil</h3><p>X-Ray Baggage &bull; 2 hari lalu</p></div>
                <span class="mobile-dashboard-badge mobile-dashboard-badge--resolved">Resolved</span>
            </article>
        </div>
        <div class="mobile-dashboard-empty" data-mobile-empty-state="findings" hidden>
            <i data-lucide="shield-check" aria-hidden="true"></i><strong>Belum ada temuan</strong><p>Belum ada temuan terbaru.</p>
        </div>
    </section>

    <section class="mobile-dashboard-card mobile-monthly-preview" data-mobile-monthly-preview aria-labelledby="mobile-monthly-title">
        <div class="mobile-dashboard-card__heading">
            <div><p>Oktober 2026</p><h2 id="mobile-monthly-title">Preview Laporan Bulanan</h2></div>
            <span class="mobile-monthly-preview__icon" aria-hidden="true"><i data-lucide="file-chart-column"></i></span>
        </div>
        <div class="mobile-monthly-preview__metrics">
            <div><small>Total Pemeriksaan</small><strong>186</strong></div>
            <div><small>Sesuai</small><strong>172</strong></div>
            <div><small>Tidak Sesuai</small><strong>14</strong></div>
        </div>
        <div class="mobile-monthly-preview__completion"><span><strong>92%</strong> penyelesaian</span><div><i></i></div></div>
        <button class="mobile-dashboard-button mobile-dashboard-button--secondary" type="button" data-mobile-dashboard-action="report">Lihat Laporan<i data-lucide="arrow-right" aria-hidden="true"></i></button>
    </section>

    <section class="mobile-dashboard-section" aria-labelledby="mobile-quick-actions-title">
        <div class="mobile-dashboard-section__heading"><div><p>Akses utama</p><h2 id="mobile-quick-actions-title">Aksi Cepat</h2></div></div>
        <div class="mobile-quick-actions">
            <button type="button" data-mobile-dashboard-action="inspection"><span><i data-lucide="clipboard-check" aria-hidden="true"></i></span><strong>Mulai Pemeriksaan</strong><small>Checklist hari ini</small></button>
            <button type="button" data-mobile-dashboard-action="schedules"><span><i data-lucide="calendar-clock" aria-hidden="true"></i></span><strong>Lihat Jadwal</strong><small>Agenda maintenance</small></button>
            <button type="button" data-mobile-dashboard-action="findings"><span><i data-lucide="triangle-alert" aria-hidden="true"></i></span><strong>Lihat Temuan</strong><small>Tindak lanjut</small></button>
            <button type="button" data-mobile-dashboard-action="report"><span><i data-lucide="file-chart-column" aria-hidden="true"></i></span><strong>Laporan Bulanan</strong><small>Rekap Oktober</small></button>
        </div>
    </section>

    <div class="mobile-dashboard-toast" data-mobile-dashboard-toast role="status" aria-live="polite" hidden>
        <i data-lucide="circle-check" aria-hidden="true"></i><span></span>
    </div>
</div>
