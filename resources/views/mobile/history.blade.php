@php
    $inspectionHistories = [
        [
            'id' => 'PM-091026-001',
            'date' => '2026-10-09',
            'date_label' => '09 Okt 2026',
            'machine_key' => 'baggage',
            'machine' => 'X-Ray Baggage HI-Scan 100100T',
            'brand' => 'Smiths Detection',
            'type' => 'HI-SCAN 100100T',
            'serial_number' => 'HS-100100T-2601',
            'location' => 'Terminal 1',
            'frequency' => 'Harian',
            'officer_key' => 'budi',
            'officer' => 'Budi Santoso',
            'result_key' => 'normal',
            'result' => 'Normal',
            'verification_key' => 'verified',
            'verification' => 'Terverifikasi',
            'note' => 'Seluruh item pemeriksaan harian berada dalam kondisi normal.',
            'supervisor_note' => 'Hasil pemeriksaan telah ditinjau dan dapat dilanjutkan untuk operasional.',
            'checklist' => [
                ['category' => 'Safety Check', 'item' => 'Lead curtain', 'criteria' => 'Tidak robek dan menutup dengan baik', 'result_key' => 'normal', 'result' => 'Sesuai', 'note' => '-'],
                ['category' => 'Monitor', 'item' => 'Tampilan gambar jelas', 'criteria' => 'Tidak buram dan tidak berkedip', 'result_key' => 'normal', 'result' => 'Sesuai', 'note' => '-'],
                ['category' => 'Power System', 'item' => 'UPS', 'criteria' => 'Backup daya bekerja normal', 'result_key' => 'normal', 'result' => 'Sesuai', 'note' => '-'],
            ],
        ],
        [
            'id' => 'PM-081026-002',
            'date' => '2026-10-08',
            'date_label' => '08 Okt 2026',
            'machine_key' => 'cabin',
            'machine' => 'X-Ray Cabin',
            'brand' => 'Rapiscan',
            'type' => '620XR HP',
            'serial_number' => 'RP-620XR-1842',
            'location' => 'Terminal 2',
            'frequency' => 'Harian',
            'officer_key' => 'andi',
            'officer' => 'Andi Pratama',
            'result_key' => 'finding',
            'result' => 'Terdapat Temuan',
            'verification_key' => 'pending',
            'verification' => 'Menunggu Verifikasi',
            'note' => 'Tampilan monitor terlihat kurang tajam saat menampilkan objek uji.',
            'supervisor_note' => 'Belum ada catatan. Pemeriksaan menunggu tinjauan supervisor.',
            'checklist' => [
                ['category' => 'Safety Check', 'item' => 'Lead curtain', 'criteria' => 'Tidak robek dan menutup dengan baik', 'result_key' => 'normal', 'result' => 'Sesuai', 'note' => '-'],
                ['category' => 'Monitor', 'item' => 'Tampilan gambar jelas', 'criteria' => 'Tidak buram dan tidak berkedip', 'result_key' => 'finding', 'result' => 'Tidak Sesuai', 'note' => 'Perlu kalibrasi'],
                ['category' => 'Power System', 'item' => 'UPS', 'criteria' => 'Backup daya bekerja normal', 'result_key' => 'normal', 'result' => 'Sesuai', 'note' => '-'],
            ],
        ],
        [
            'id' => 'PM-071026-003',
            'date' => '2026-10-07',
            'date_label' => '07 Okt 2026',
            'machine_key' => 'cargo',
            'machine' => 'X-Ray Cargo',
            'brand' => 'Smiths Detection',
            'type' => 'HI-SCAN 145180-2is',
            'serial_number' => 'HS-145180-1917',
            'location' => 'Area Cargo',
            'frequency' => 'Mingguan',
            'officer_key' => 'siti',
            'officer' => 'Siti Rahma',
            'result_key' => 'normal',
            'result' => 'Normal',
            'verification_key' => 'verified',
            'verification' => 'Terverifikasi',
            'note' => 'Pemeriksaan mingguan selesai tanpa temuan.',
            'supervisor_note' => 'Hasil pengujian sesuai standar preventive maintenance.',
            'checklist' => [
                ['category' => 'Conveyor', 'item' => 'Conveyor belt', 'criteria' => 'Berjalan stabil dan tidak miring', 'result_key' => 'normal', 'result' => 'Sesuai', 'note' => '-'],
                ['category' => 'Safety Check', 'item' => 'Emergency stop', 'criteria' => 'Menghentikan unit seketika', 'result_key' => 'normal', 'result' => 'Sesuai', 'note' => '-'],
                ['category' => 'Power System', 'item' => 'UPS', 'criteria' => 'Backup daya bekerja normal', 'result_key' => 'normal', 'result' => 'Sesuai', 'note' => '-'],
            ],
        ],
        [
            'id' => 'PM-061026-004',
            'date' => '2026-10-06',
            'date_label' => '06 Okt 2026',
            'machine_key' => 'baggage',
            'machine' => 'X-Ray Baggage HI-Scan 100100T',
            'brand' => 'Smiths Detection',
            'type' => 'HI-SCAN 100100T',
            'serial_number' => 'HS-100100T-2601',
            'location' => 'Terminal 1',
            'frequency' => 'Bulanan',
            'officer_key' => 'siti',
            'officer' => 'Siti Rahma',
            'result_key' => 'normal',
            'result' => 'Normal',
            'verification_key' => 'pending',
            'verification' => 'Menunggu Verifikasi',
            'note' => 'Pemeriksaan bulanan telah diselesaikan dan seluruh fungsi utama normal.',
            'supervisor_note' => 'Menunggu proses verifikasi supervisor.',
            'checklist' => [
                ['category' => 'Radiation', 'item' => 'Leakage Radiation Test', 'criteria' => 'Nilai kebocoran di bawah ambang batas', 'result_key' => 'normal', 'result' => 'Sesuai', 'note' => '0,8 µSv/jam'],
                ['category' => 'Monitor', 'item' => 'Kualitas citra', 'criteria' => 'Citra tajam dan stabil', 'result_key' => 'normal', 'result' => 'Sesuai', 'note' => '-'],
            ],
        ],
        [
            'id' => 'PM-051026-005',
            'date' => '2026-10-05',
            'date_label' => '05 Okt 2026',
            'machine_key' => 'cabin',
            'machine' => 'X-Ray Cabin',
            'brand' => 'Rapiscan',
            'type' => '620XR HP',
            'serial_number' => 'RP-620XR-1842',
            'location' => 'Terminal 2',
            'frequency' => 'Mingguan',
            'officer_key' => 'budi',
            'officer' => 'Budi Santoso',
            'result_key' => 'finding',
            'result' => 'Terdapat Temuan',
            'verification_key' => 'verified',
            'verification' => 'Terverifikasi',
            'note' => 'Lampu indikator ready tidak menyala setelah startup.',
            'supervisor_note' => 'Temuan telah diteruskan untuk pemeriksaan kabel dan lampu indikator.',
            'checklist' => [
                ['category' => 'Indicator', 'item' => 'Lampu ready', 'criteria' => 'Menyala setelah proses startup', 'result_key' => 'finding', 'result' => 'Tidak Sesuai', 'note' => 'Lampu tidak menyala'],
                ['category' => 'Conveyor', 'item' => 'Conveyor belt', 'criteria' => 'Berjalan stabil dan tidak miring', 'result_key' => 'normal', 'result' => 'Sesuai', 'note' => '-'],
            ],
        ],
        [
            'id' => 'PM-031026-006',
            'date' => '2026-10-03',
            'date_label' => '03 Okt 2026',
            'machine_key' => 'cargo',
            'machine' => 'X-Ray Cargo',
            'brand' => 'Smiths Detection',
            'type' => 'HI-SCAN 145180-2is',
            'serial_number' => 'HS-145180-1917',
            'location' => 'Area Cargo',
            'frequency' => 'Bulanan',
            'officer_key' => 'andi',
            'officer' => 'Andi Pratama',
            'result_key' => 'normal',
            'result' => 'Normal',
            'verification_key' => 'verified',
            'verification' => 'Terverifikasi',
            'note' => 'Seluruh item bulanan memenuhi kriteria pemeriksaan.',
            'supervisor_note' => 'Hasil pemeriksaan disetujui.',
            'checklist' => [
                ['category' => 'Exterior', 'item' => 'Unit bagian luar', 'criteria' => 'Bersih dan tidak terdapat kerusakan', 'result_key' => 'normal', 'result' => 'Sesuai', 'note' => '-'],
                ['category' => 'Power System', 'item' => 'UPS', 'criteria' => 'Backup daya bekerja normal', 'result_key' => 'normal', 'result' => 'Sesuai', 'note' => '-'],
            ],
        ],
    ];
@endphp

<div class="mobile-history" data-mobile-history>
    <header class="mobile-history-header">
        <span class="mobile-history-header__icon" aria-hidden="true"><i data-lucide="history"></i></span>
        <div>
            <p>Dokumentasi maintenance</p>
            <h1>Riwayat Pemeriksaan</h1>
            <span>Lihat pemeriksaan yang sudah dilakukan</span>
        </div>
    </header>

    <section class="mobile-history-filter" aria-labelledby="mobile-history-filter-title">
        <div class="mobile-history-section-heading">
            <span aria-hidden="true"><i data-lucide="list-filter"></i></span>
            <div>
                <h2 id="mobile-history-filter-title">Filter Riwayat</h2>
                <p>Persempit data pemeriksaan yang ingin dilihat.</p>
            </div>
        </div>

        <form data-mobile-history-filter>
            <div class="mobile-history-filter__dates">
                <label><span>Tanggal mulai</span><input type="date" name="startDate"></label>
                <label><span>Tanggal akhir</span><input type="date" name="endDate"></label>
            </div>
            <label><span>Mesin</span><select name="machine"><option value="">Semua Mesin</option><option value="baggage">X-Ray Baggage HI-Scan 100100T</option><option value="cabin">X-Ray Cabin</option><option value="cargo">X-Ray Cargo</option></select></label>
            <label><span>Status</span><select name="status"><option value="">Semua Status</option><option value="normal">Normal</option><option value="finding">Terdapat Temuan</option><option value="pending">Menunggu Verifikasi</option><option value="verified">Terverifikasi</option></select></label>
            <label><span>Petugas</span><select name="officer"><option value="">Semua Petugas</option><option value="budi">Budi Santoso</option><option value="andi">Andi Pratama</option><option value="siti">Siti Rahma</option></select></label>
            <label class="mobile-history-filter__search"><span>Cari riwayat</span><span><i data-lucide="search" aria-hidden="true"></i><input type="search" name="query" placeholder="Mesin, lokasi, atau petugas..."></span></label>
            <div class="mobile-history-filter__actions">
                <button type="submit"><i data-lucide="filter" aria-hidden="true"></i>Terapkan</button>
                <button type="button" data-mobile-history-reset><i data-lucide="rotate-ccw" aria-hidden="true"></i>Reset</button>
            </div>
        </form>
    </section>

    <section class="mobile-history-summary" aria-label="Ringkasan riwayat pemeriksaan">
        <article class="mobile-history-summary__card mobile-history-summary__card--total"><span aria-hidden="true"><i data-lucide="clipboard-list"></i></span><div><small>Total Pemeriksaan</small><strong data-mobile-history-summary="total">6</strong></div></article>
        <article class="mobile-history-summary__card mobile-history-summary__card--normal"><span aria-hidden="true"><i data-lucide="circle-check-big"></i></span><div><small>Normal</small><strong data-mobile-history-summary="normal">4</strong></div></article>
        <article class="mobile-history-summary__card mobile-history-summary__card--finding"><span aria-hidden="true"><i data-lucide="triangle-alert"></i></span><div><small>Terdapat Temuan</small><strong data-mobile-history-summary="finding">2</strong></div></article>
        <article class="mobile-history-summary__card mobile-history-summary__card--pending"><span aria-hidden="true"><i data-lucide="clock-3"></i></span><div><small>Menunggu Verifikasi</small><strong data-mobile-history-summary="pending">2</strong></div></article>
    </section>

    <section class="mobile-history-list-section" aria-labelledby="mobile-history-list-title">
        <div class="mobile-history-list-section__header">
            <div><p>Data maintenance</p><h2 id="mobile-history-list-title">Daftar Riwayat</h2></div>
            <span data-mobile-history-result-count role="status" aria-live="polite">6 pemeriksaan</span>
        </div>

        <div class="mobile-history-list" data-mobile-history-list>
            @foreach ($inspectionHistories as $history)
                <article
                    class="mobile-history-card"
                    data-mobile-history-card
                    data-id="{{ $history['id'] }}"
                    data-date="{{ $history['date'] }}"
                    data-date-label="{{ $history['date_label'] }}"
                    data-machine="{{ $history['machine_key'] }}"
                    data-machine-name="{{ $history['machine'] }}"
                    data-brand="{{ $history['brand'] }}"
                    data-type="{{ $history['type'] }}"
                    data-serial-number="{{ $history['serial_number'] }}"
                    data-location="{{ $history['location'] }}"
                    data-frequency="{{ $history['frequency'] }}"
                    data-officer="{{ $history['officer_key'] }}"
                    data-officer-name="{{ $history['officer'] }}"
                    data-result-status="{{ $history['result_key'] }}"
                    data-result-label="{{ $history['result'] }}"
                    data-verification-status="{{ $history['verification_key'] }}"
                    data-verification-label="{{ $history['verification'] }}"
                    data-note="{{ $history['note'] }}"
                    data-supervisor-note="{{ $history['supervisor_note'] }}"
                    data-searchable-text="{{ Str::lower($history['machine'].' '.$history['location'].' '.$history['officer'].' '.$history['result'].' '.$history['verification'].' '.$history['id']) }}"
                    data-checklist="{{ json_encode($history['checklist'], JSON_UNESCAPED_UNICODE) }}"
                >
                    <header class="mobile-history-card__header">
                        <span class="mobile-history-card__icon" aria-hidden="true"><i data-lucide="scan-line"></i></span>
                        <div><small>{{ $history['id'] }}</small><h3>{{ $history['machine'] }}</h3><p><i data-lucide="map-pin" aria-hidden="true"></i>{{ $history['location'] }}</p></div>
                    </header>
                    <div class="mobile-history-card__meta">
                        <div><small>Tanggal</small><strong>{{ $history['date_label'] }}</strong></div>
                        <div><small>Frekuensi</small><strong>{{ $history['frequency'] }}</strong></div>
                        <div class="mobile-history-card__meta-wide"><small>Petugas</small><strong><i data-lucide="user-round" aria-hidden="true"></i>{{ $history['officer'] }}</strong></div>
                    </div>
                    <div class="mobile-history-card__statuses">
                        <div><small>Hasil</small><span class="mobile-history-badge mobile-history-badge--{{ $history['result_key'] }}">{{ $history['result'] }}</span></div>
                        <div><small>Verifikasi</small><span class="mobile-history-badge mobile-history-badge--{{ $history['verification_key'] }}">{{ $history['verification'] }}</span></div>
                    </div>
                    <footer class="mobile-history-card__actions">
                        <button type="button" data-mobile-history-detail-open data-history-id="{{ $history['id'] }}"><i data-lucide="eye" aria-hidden="true"></i>Detail</button>
                        <button type="button" data-mobile-history-print data-history-id="{{ $history['id'] }}"><i data-lucide="printer" aria-hidden="true"></i>Cetak</button>
                    </footer>
                </article>
            @endforeach
        </div>

        <div class="mobile-history-empty" data-mobile-history-empty hidden>
            <span aria-hidden="true"><i data-lucide="search-x"></i></span>
            <h3>Riwayat tidak ditemukan</h3>
            <p>Coba ubah rentang tanggal atau reset filter yang digunakan.</p>
        </div>
    </section>

    <section class="mobile-history-sheet" data-mobile-history-sheet hidden aria-hidden="true">
        <button class="mobile-history-sheet__backdrop" type="button" data-mobile-history-sheet-close tabindex="-1" aria-label="Tutup detail riwayat"></button>
        <article class="mobile-history-sheet__panel" role="dialog" aria-modal="true" aria-labelledby="mobile-history-detail-title" data-mobile-history-sheet-panel tabindex="-1">
            <div class="mobile-history-sheet__handle" aria-hidden="true"></div>
            <header class="mobile-history-sheet__header">
                <span aria-hidden="true"><i data-lucide="clipboard-search"></i></span>
                <div><small data-mobile-history-detail="id">-</small><h2 id="mobile-history-detail-title">Detail Riwayat</h2></div>
                <button type="button" data-mobile-history-sheet-close aria-label="Tutup detail"><i data-lucide="x" aria-hidden="true"></i></button>
            </header>
            <div class="mobile-history-sheet__body">
                <section class="mobile-history-detail-hero">
                    <div><small>Mesin X-Ray</small><h3 data-mobile-history-detail="machineName">-</h3><p><i data-lucide="map-pin" aria-hidden="true"></i><span data-mobile-history-detail="location">-</span></p></div>
                    <span class="mobile-history-badge" data-mobile-history-detail="verification">-</span>
                </section>
                <dl class="mobile-history-detail-grid">
                    <div><dt>Merk</dt><dd data-mobile-history-detail="brand">-</dd></div>
                    <div><dt>Tipe</dt><dd data-mobile-history-detail="type">-</dd></div>
                    <div class="mobile-history-detail-grid__wide"><dt>Serial number</dt><dd data-mobile-history-detail="serialNumber">-</dd></div>
                    <div><dt>Tanggal pemeriksaan</dt><dd data-mobile-history-detail="dateLabel">-</dd></div>
                    <div><dt>Frekuensi</dt><dd data-mobile-history-detail="frequency">-</dd></div>
                    <div class="mobile-history-detail-grid__wide"><dt>Petugas</dt><dd data-mobile-history-detail="officerName">-</dd></div>
                    <div><dt>Hasil pemeriksaan</dt><dd><span class="mobile-history-badge" data-mobile-history-detail="result">-</span></dd></div>
                    <div><dt>Status verifikasi</dt><dd data-mobile-history-detail="verificationLabel">-</dd></div>
                </dl>
                <section class="mobile-history-detail-note"><h3><i data-lucide="notebook-pen" aria-hidden="true"></i>Catatan Pemeriksaan</h3><p data-mobile-history-detail="note">-</p></section>
                <section class="mobile-history-detail-note mobile-history-detail-note--supervisor"><h3><i data-lucide="shield-check" aria-hidden="true"></i>Catatan Supervisor</h3><p data-mobile-history-detail="supervisorNote">-</p></section>
                <section class="mobile-history-checklist" aria-labelledby="mobile-history-checklist-title">
                    <div class="mobile-history-checklist__heading"><div><p>Hasil per item</p><h3 id="mobile-history-checklist-title">Detail Checklist</h3></div><span data-mobile-history-checklist-count>0 item</span></div>
                    <div class="mobile-history-checklist__list" data-mobile-history-checklist></div>
                </section>
            </div>
            <footer class="mobile-history-sheet__footer">
                <button type="button" data-mobile-history-sheet-print><i data-lucide="printer" aria-hidden="true"></i>Cetak Riwayat</button>
                <button type="button" data-mobile-history-sheet-close>Tutup</button>
            </footer>
        </article>
    </section>

    <div class="mobile-history-toast" role="status" aria-live="polite" data-mobile-history-toast hidden>
        <i data-lucide="printer-check" aria-hidden="true"></i>
        <p data-mobile-history-toast-message>Dokumen sedang disiapkan.</p>
        <button type="button" data-mobile-history-toast-close aria-label="Tutup notifikasi"><i data-lucide="x" aria-hidden="true"></i></button>
    </div>
</div>
