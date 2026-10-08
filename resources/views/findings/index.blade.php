@extends('layouts.app')

@section('title', 'Temuan Pemeriksaan')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/findings.css') }}">
@endpush

@php
    $findings = [
        [
            'id' => 'FND-2026-001',
            'date' => '2026-10-08',
            'date_label' => '08 Okt 2026',
            'machine' => 'Mesin X-Ray Baggage - Full Baggage',
            'machine_key' => 'baggage',
            'location' => 'Terminal 1',
            'frequency' => 'Harian',
            'frequency_key' => 'daily',
            'item' => 'Conveyor belt tidak stabil',
            'criteria' => 'Conveyor bergerak stabil, tidak tersendat, dan tidak bergeser.',
            'description' => 'Pergerakan belt tersendat dan terdengar suara gesekan saat unit dijalankan.',
            'follow_up' => 'Pemeriksaan motor penggerak dan penyetelan ulang ketegangan belt.',
            'status' => 'Open',
            'status_key' => 'open',
            'has_photo' => true,
            'photo_variant' => 'conveyor',
            'photo_label' => 'Dokumentasi conveyor belt',
            'officer' => 'Budi Santoso',
            'supervisor_note' => 'Segera koordinasikan pemeriksaan dengan teknisi sebelum jam operasional berikutnya.',
        ],
        [
            'id' => 'FND-2026-002',
            'date' => '2026-10-08',
            'date_label' => '08 Okt 2026',
            'machine' => 'Mesin X-Ray Cabin Baggage',
            'machine_key' => 'cabin',
            'location' => 'Terminal 2',
            'frequency' => 'Harian',
            'frequency_key' => 'daily',
            'item' => 'Indicator lamp tidak menyala',
            'criteria' => 'Seluruh lampu indikator menyala sesuai fungsi dan kondisi unit.',
            'description' => 'Lampu indikator status ready tidak menyala setelah proses startup selesai.',
            'follow_up' => 'Pengujian tegangan dan penggantian lampu indikator bila diperlukan.',
            'status' => 'Review',
            'status_key' => 'review',
            'has_photo' => true,
            'photo_variant' => 'indicator',
            'photo_label' => 'Dokumentasi indicator lamp',
            'officer' => 'Andi Pratama',
            'supervisor_note' => 'Pastikan ketersediaan spare part dan lakukan pengecekan jalur kelistrikan.',
        ],
        [
            'id' => 'FND-2026-003',
            'date' => '2026-10-07',
            'date_label' => '07 Okt 2026',
            'machine' => 'Mesin X-Ray Cargo',
            'machine_key' => 'cargo',
            'location' => 'Area Cargo',
            'frequency' => 'Mingguan',
            'frequency_key' => 'weekly',
            'item' => 'UPS tidak normal',
            'criteria' => 'UPS mempertahankan daya cadangan dan indikator menunjukkan kondisi normal.',
            'description' => 'Durasi daya cadangan berada di bawah standar ketika sumber utama diputus.',
            'follow_up' => 'Baterai UPS telah diganti dan pengujian beban ulang dinyatakan normal.',
            'status' => 'Resolved',
            'status_key' => 'resolved',
            'has_photo' => false,
            'photo_variant' => 'ups',
            'photo_label' => 'Dokumentasi UPS',
            'officer' => 'Siti Rahma',
            'supervisor_note' => 'Penggantian baterai dan hasil uji ulang telah dikonfirmasi.',
        ],
        [
            'id' => 'FND-2026-004',
            'date' => '2026-10-06',
            'date_label' => '06 Okt 2026',
            'machine' => 'Mesin X-Ray Baggage - Full Baggage',
            'machine_key' => 'baggage',
            'location' => 'Terminal 1',
            'frequency' => 'Bulanan',
            'frequency_key' => 'monthly',
            'item' => 'Monitor menampilkan citra buram',
            'criteria' => 'Citra objek tampil tajam, stabil, dan dapat diidentifikasi dengan jelas.',
            'description' => 'Citra tampak buram pada sisi kanan monitor meskipun pengaturan kontras telah disesuaikan.',
            'follow_up' => 'Pembersihan konektor display dan pemeriksaan modul pengolah citra.',
            'status' => 'Open',
            'status_key' => 'open',
            'has_photo' => true,
            'photo_variant' => 'monitor',
            'photo_label' => 'Dokumentasi monitor buram',
            'officer' => 'Budi Santoso',
            'supervisor_note' => 'Batasi penggunaan unit hingga penyebab citra buram teridentifikasi.',
        ],
        [
            'id' => 'FND-2026-005',
            'date' => '2026-10-05',
            'date_label' => '05 Okt 2026',
            'machine' => 'Mesin X-Ray Cabin Baggage',
            'machine_key' => 'cabin',
            'location' => 'Terminal 2',
            'frequency' => 'Triwulan',
            'frequency_key' => 'quarterly',
            'item' => 'Lead curtain mulai sobek',
            'criteria' => 'Lead curtain utuh, tidak sobek, dan menutup jalur masuk serta keluar.',
            'description' => 'Ditemukan sobekan kecil pada sisi bawah lead curtain jalur keluar.',
            'follow_up' => 'Pengukuran area sobek dan pengajuan penggantian lead curtain.',
            'status' => 'Review',
            'status_key' => 'review',
            'has_photo' => false,
            'photo_variant' => 'curtain',
            'photo_label' => 'Dokumentasi lead curtain',
            'officer' => 'Andi Pratama',
            'supervisor_note' => 'Verifikasi tingkat kerusakan dan jadwalkan penggantian komponen.',
        ],
        [
            'id' => 'FND-2026-006',
            'date' => '2026-10-03',
            'date_label' => '03 Okt 2026',
            'machine' => 'Mesin X-Ray Cargo',
            'machine_key' => 'cargo',
            'location' => 'Area Cargo',
            'frequency' => 'Semesteran',
            'frequency_key' => 'semester',
            'item' => 'Emergency stop terlambat merespons',
            'criteria' => 'Unit berhenti seketika saat tombol emergency stop ditekan.',
            'description' => 'Terdapat jeda respons sekitar dua detik pada pengujian pertama.',
            'follow_up' => 'Kontak tombol dibersihkan dan respons diuji ulang sebanyak tiga kali.',
            'status' => 'Resolved',
            'status_key' => 'resolved',
            'has_photo' => true,
            'photo_variant' => 'emergency',
            'photo_label' => 'Dokumentasi emergency stop',
            'officer' => 'Siti Rahma',
            'supervisor_note' => 'Hasil pengujian ulang sesuai standar dan temuan dinyatakan selesai.',
        ],
    ];
@endphp

@section('content')
    <div class="findings-page">
        <header class="findings-page-header">
            <div>
                <p class="findings-page-header__eyebrow">Monitoring Maintenance</p>
                <h2>Temuan Pemeriksaan</h2>
                <p>Kelola temuan dari hasil preventive maintenance mesin X-Ray.</p>
            </div>
            <button class="button button--primary" type="button" data-finding-create>
                <i data-lucide="plus" aria-hidden="true"></i>
                Tambah Temuan
            </button>
        </header>

        <section class="findings-summary" aria-label="Ringkasan temuan pemeriksaan">
            <article class="findings-summary-card findings-summary-card--total">
                <span class="findings-summary-card__icon" aria-hidden="true"><i data-lucide="clipboard-list"></i></span>
                <div><p>Total Temuan</p><strong data-summary-total>6</strong><span>Seluruh temuan tercatat</span></div>
            </article>
            <article class="findings-summary-card findings-summary-card--open">
                <span class="findings-summary-card__icon" aria-hidden="true"><i data-lucide="circle-alert"></i></span>
                <div><p>Open</p><strong data-summary-open>2</strong><span>Perlu tindak lanjut</span></div>
            </article>
            <article class="findings-summary-card findings-summary-card--review">
                <span class="findings-summary-card__icon" aria-hidden="true"><i data-lucide="search-check"></i></span>
                <div><p>Review</p><strong data-summary-review>2</strong><span>Dalam verifikasi</span></div>
            </article>
            <article class="findings-summary-card findings-summary-card--resolved">
                <span class="findings-summary-card__icon" aria-hidden="true"><i data-lucide="circle-check-big"></i></span>
                <div><p>Resolved</p><strong data-summary-resolved>2</strong><span>Sudah diselesaikan</span></div>
            </article>
        </section>

        <section class="findings-filter-panel" aria-labelledby="findings-filter-title">
            <div class="findings-section-heading">
                <div>
                    <span class="findings-section-heading__icon" aria-hidden="true"><i data-lucide="list-filter"></i></span>
                    <div><h3 id="findings-filter-title">Filter Temuan</h3><p>Temukan data berdasarkan periode, mesin, status, atau kata kunci.</p></div>
                </div>
            </div>
            <form class="findings-filter" data-findings-filter>
                <label class="field"><span>Tanggal</span><input type="date" name="date" data-filter-date></label>
                <label class="field"><span>Mesin</span><select name="machine" data-filter-machine><option value="">Semua Mesin</option><option value="baggage">X-Ray Baggage - Full Baggage</option><option value="cabin">X-Ray Cabin Baggage</option><option value="cargo">X-Ray Cargo</option></select></label>
                <label class="field"><span>Status</span><select name="status" data-filter-status><option value="">Semua Status</option><option value="open">Open</option><option value="review">Review</option><option value="resolved">Resolved</option></select></label>
                <label class="field"><span>Frekuensi</span><select name="frequency" data-filter-frequency><option value="">Semua Frekuensi</option><option value="daily">Harian</option><option value="weekly">Mingguan</option><option value="monthly">Bulanan</option><option value="quarterly">Triwulan</option><option value="semester">Semesteran</option><option value="yearly">Tahunan</option></select></label>
                <label class="field findings-filter__search"><span>Pencarian</span><span class="input-with-icon"><i data-lucide="search" aria-hidden="true"></i><input type="search" name="search" placeholder="Cari mesin atau item..." data-filter-search></span></label>
                <div class="findings-filter__actions">
                    <button class="button button--primary" type="submit"><i data-lucide="filter" aria-hidden="true"></i>Terapkan</button>
                    <button class="button button--secondary" type="reset" data-filter-reset><i data-lucide="rotate-ccw" aria-hidden="true"></i>Reset</button>
                </div>
            </form>
        </section>

        <section class="findings-table-panel" aria-labelledby="findings-table-title">
            <div class="findings-table-panel__header">
                <div>
                    <h3 id="findings-table-title">Daftar Temuan</h3>
                    <p><span data-visible-count>6</span> dari 6 data ditampilkan</p>
                </div>
                <span class="findings-table-panel__period"><i data-lucide="calendar-days" aria-hidden="true"></i>Oktober 2026</span>
            </div>
            <div class="findings-table-scroll">
                <table class="findings-table">
                    <thead><tr><th>No</th><th>Tanggal</th><th>Mesin</th><th>Lokasi</th><th>Item Tidak Sesuai</th><th>Keterangan Temuan</th><th>Tindak Lanjut</th><th>Status</th><th>Foto</th><th>Aksi</th></tr></thead>
                    <tbody data-findings-body>
                        @foreach ($findings as $finding)
                            <tr data-finding-row data-date="{{ $finding['date'] }}" data-machine="{{ $finding['machine_key'] }}" data-status="{{ $finding['status_key'] }}" data-frequency="{{ $finding['frequency_key'] }}" data-search="{{ Str::lower($finding['machine'].' '.$finding['item']) }}" data-finding="{{ json_encode($finding, JSON_UNESCAPED_UNICODE) }}">
                                <td class="findings-table__number">{{ $loop->iteration }}</td>
                                <td><time datetime="{{ $finding['date'] }}">{{ $finding['date_label'] }}</time><small>{{ $finding['frequency'] }}</small></td>
                                <td><strong>{{ $finding['machine'] }}</strong><small>{{ $finding['id'] }}</small></td>
                                <td>{{ $finding['location'] }}</td>
                                <td><strong>{{ $finding['item'] }}</strong></td>
                                <td class="findings-table__description">{{ $finding['description'] }}</td>
                                <td class="findings-table__description">{{ $finding['follow_up'] }}</td>
                                <td><span class="status-badge status-badge--{{ $finding['status_key'] }}" data-row-status><span aria-hidden="true"></span>{{ $finding['status'] }}</span></td>
                                <td>
                                    @if ($finding['has_photo'])
                                        <button class="finding-photo-button" type="button" data-photo-preview aria-label="Lihat {{ $finding['photo_label'] }}">
                                            <span class="finding-photo finding-photo--{{ $finding['photo_variant'] }}" aria-hidden="true"><i data-lucide="scan-line"></i><span></span></span>
                                            <small>Lihat foto</small>
                                        </button>
                                    @else
                                        <span class="finding-photo-empty"><i data-lucide="image-off" aria-hidden="true"></i>Tidak ada foto</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <button type="button" class="icon-button" data-finding-detail title="Lihat detail" aria-label="Lihat detail {{ $finding['id'] }}"><i data-lucide="eye" aria-hidden="true"></i></button>
                                        <button type="button" class="icon-button" data-finding-edit title="Edit temuan" aria-label="Edit {{ $finding['id'] }}"><i data-lucide="pencil" aria-hidden="true"></i></button>
                                        <button type="button" class="icon-button icon-button--status" data-finding-status title="Ubah status" aria-label="Ubah status {{ $finding['id'] }}"><i data-lucide="refresh-cw" aria-hidden="true"></i></button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        <tr class="findings-table__empty" data-findings-empty hidden><td colspan="10"><i data-lucide="search-x" aria-hidden="true"></i><strong>Data tidak ditemukan</strong><span>Coba ubah atau reset filter yang digunakan.</span></td></tr>
                    </tbody>
                </table>
            </div>
            <footer class="findings-table-panel__footer"><p>Menampilkan <strong data-footer-count>6</strong> data temuan</p><span>Data simulasi • Oktober 2026</span></footer>
        </section>
    </div>

    <dialog class="findings-dialog findings-dialog--form" id="finding-form-dialog" aria-labelledby="finding-form-title">
        <form class="findings-dialog__surface" data-finding-form>
            <header class="findings-dialog__header"><div><span class="findings-dialog__icon" aria-hidden="true"><i data-lucide="file-pen-line"></i></span><div><h3 id="finding-form-title" data-form-title>Tambah Temuan</h3><p data-form-description>Catat temuan baru dari hasil pemeriksaan.</p></div></div><button type="button" class="dialog-close" data-dialog-close aria-label="Tutup dialog"><i data-lucide="x" aria-hidden="true"></i></button></header>
            <div class="findings-dialog__body findings-form-grid">
                <input type="hidden" name="finding_id">
                <label class="field"><span>Tanggal Pemeriksaan</span><input type="date" name="date" value="2026-10-08" required></label>
                <label class="field"><span>Mesin</span><select name="machine_key" required><option value="">Pilih mesin</option><option value="baggage">Mesin X-Ray Baggage - Full Baggage</option><option value="cabin">Mesin X-Ray Cabin Baggage</option><option value="cargo">Mesin X-Ray Cargo</option></select></label>
                <label class="field"><span>Lokasi</span><input type="text" name="location" placeholder="Contoh: Terminal 1" required></label>
                <label class="field"><span>Frekuensi</span><select name="frequency_key" required><option value="">Pilih frekuensi</option><option value="daily">Harian</option><option value="weekly">Mingguan</option><option value="monthly">Bulanan</option><option value="quarterly">Triwulan</option><option value="semester">Semesteran</option><option value="yearly">Tahunan</option></select></label>
                <label class="field findings-form-grid__wide"><span>Item Tidak Sesuai</span><input type="text" name="item" placeholder="Masukkan item pemeriksaan" required></label>
                <label class="field findings-form-grid__wide"><span>Keterangan Temuan</span><textarea name="description" rows="3" placeholder="Jelaskan kondisi yang ditemukan" required></textarea></label>
                <label class="field findings-form-grid__wide"><span>Tindak Lanjut</span><textarea name="follow_up" rows="3" placeholder="Jelaskan tindak lanjut yang direncanakan" required></textarea></label>
                <label class="field"><span>Status</span><select name="status_key" required><option value="open">Open</option><option value="review">Review</option><option value="resolved">Resolved</option></select></label>
                <label class="field findings-form-grid__wide"><span>Foto Temuan <small>(opsional)</small></span><span class="finding-upload"><input type="file" name="photo" accept="image/*" data-photo-input><span class="finding-upload__placeholder" data-upload-placeholder><i data-lucide="image-up" aria-hidden="true"></i><strong>Pilih foto dokumentasi</strong><small>JPG, PNG, atau WEBP</small></span><img src="" alt="Preview foto temuan" data-upload-preview hidden></span></label>
            </div>
            <footer class="findings-dialog__footer"><button class="button button--secondary" type="button" data-dialog-close>Batal</button><button class="button button--primary" type="submit"><i data-lucide="save" aria-hidden="true"></i>Simpan Temuan</button></footer>
        </form>
    </dialog>

    <dialog class="findings-dialog findings-dialog--detail" id="finding-detail-dialog" aria-labelledby="finding-detail-title">
        <article class="findings-dialog__surface">
            <header class="findings-dialog__header"><div><span class="findings-dialog__icon" aria-hidden="true"><i data-lucide="clipboard-search"></i></span><div><h3 id="finding-detail-title">Detail Temuan</h3><p data-detail-id>FND-2026-001</p></div></div><button type="button" class="dialog-close" data-dialog-close aria-label="Tutup dialog"><i data-lucide="x" aria-hidden="true"></i></button></header>
            <div class="findings-dialog__body">
                <div class="finding-detail-hero"><div><span>Item Tidak Sesuai</span><h4 data-detail-field="item">-</h4><p data-detail-field="machine">-</p></div><span class="status-badge" data-detail-status>-</span></div>
                <dl class="finding-detail-grid">
                    <div><dt>Tanggal</dt><dd data-detail-field="date_label">-</dd></div><div><dt>Lokasi</dt><dd data-detail-field="location">-</dd></div><div><dt>Frekuensi</dt><dd data-detail-field="frequency">-</dd></div><div><dt>Petugas</dt><dd data-detail-field="officer">-</dd></div>
                    <div class="finding-detail-grid__wide"><dt>Kriteria Pemeriksaan</dt><dd data-detail-field="criteria">-</dd></div><div class="finding-detail-grid__wide"><dt>Keterangan Temuan</dt><dd data-detail-field="description">-</dd></div><div class="finding-detail-grid__wide"><dt>Tindak Lanjut</dt><dd data-detail-field="follow_up">-</dd></div><div class="finding-detail-grid__wide"><dt>Catatan Supervisor</dt><dd data-detail-field="supervisor_note">-</dd></div>
                </dl>
                <div class="finding-detail-photo" data-detail-photo><span class="finding-photo finding-photo--conveyor" aria-hidden="true"><i data-lucide="scan-line"></i><span></span></span><div><strong>Foto dokumentasi</strong><small data-detail-photo-label>-</small></div></div>
                <div class="finding-detail-photo finding-detail-photo--empty" data-detail-photo-empty hidden><i data-lucide="image-off" aria-hidden="true"></i><span>Foto dokumentasi tidak tersedia.</span></div>
            </div>
            <footer class="findings-dialog__footer"><button class="button button--primary" type="button" data-dialog-close>Tutup Detail</button></footer>
        </article>
    </dialog>

    <dialog class="findings-dialog findings-dialog--status" id="finding-status-dialog" aria-labelledby="finding-status-title">
        <form class="findings-dialog__surface" data-status-form>
            <header class="findings-dialog__header"><div><span class="findings-dialog__icon" aria-hidden="true"><i data-lucide="refresh-cw"></i></span><div><h3 id="finding-status-title">Ubah Status Temuan</h3><p data-status-finding-label>Pilih status terbaru.</p></div></div><button type="button" class="dialog-close" data-dialog-close aria-label="Tutup dialog"><i data-lucide="x" aria-hidden="true"></i></button></header>
            <div class="findings-dialog__body"><label class="field"><span>Status Temuan</span><select name="status" required><option value="open">Open</option><option value="review">Review</option><option value="resolved">Resolved</option></select></label><p class="status-dialog-note"><i data-lucide="info" aria-hidden="true"></i>Perubahan hanya disimulasikan pada halaman ini dan belum disimpan ke database.</p></div>
            <footer class="findings-dialog__footer"><button class="button button--secondary" type="button" data-dialog-close>Batal</button><button class="button button--primary" type="submit">Simpan Status</button></footer>
        </form>
    </dialog>

    <dialog class="findings-dialog findings-dialog--photo" id="finding-photo-dialog" aria-labelledby="finding-photo-title">
        <article class="findings-dialog__surface"><header class="findings-dialog__header"><div><span class="findings-dialog__icon" aria-hidden="true"><i data-lucide="image"></i></span><div><h3 id="finding-photo-title">Foto Temuan</h3><p data-photo-dialog-label>Dokumentasi pemeriksaan</p></div></div><button type="button" class="dialog-close" data-dialog-close aria-label="Tutup dialog"><i data-lucide="x" aria-hidden="true"></i></button></header><div class="findings-dialog__body"><div class="finding-photo-preview finding-photo--conveyor" data-photo-dialog-preview><i data-lucide="scan-line" aria-hidden="true"></i><span></span><small>Preview dokumentasi temuan</small></div></div></article>
    </dialog>

    <div class="findings-toast" role="status" aria-live="polite" data-findings-toast hidden><i data-lucide="circle-check" aria-hidden="true"></i><span></span></div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('assets/js/findings.js') }}"></script>
@endpush
