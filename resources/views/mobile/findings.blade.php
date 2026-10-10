@php
    $mobileFindings = [
        [
            'id' => 'mobile-finding-1',
            'machine' => 'X-Ray Cabin',
            'machine_key' => 'cabin',
            'location' => 'Terminal 2',
            'date' => '2026-10-10',
            'date_label' => '10 Okt 2026',
            'item' => 'Indicator lamp tidak menyala',
            'criteria' => 'Seluruh lampu indikator menyala sesuai fungsi unit.',
            'description' => 'Lampu indikator ready tidak menyala setelah proses startup.',
            'follow_up' => 'Perlu cek kabel dan lampu indikator.',
            'status' => 'review',
            'status_label' => 'Review',
            'officer' => 'Andi Pratama',
            'has_photo' => true,
            'photo_label' => 'Foto indicator lamp',
        ],
        [
            'id' => 'mobile-finding-2',
            'machine' => 'X-Ray Cargo',
            'machine_key' => 'cargo',
            'location' => 'Area Cargo',
            'date' => '2026-10-10',
            'date_label' => '10 Okt 2026',
            'item' => 'UPS tidak normal',
            'criteria' => 'UPS mampu mempertahankan daya cadangan sesuai standar.',
            'description' => 'Durasi daya cadangan berada di bawah standar pengujian.',
            'follow_up' => 'Perlu ganti battery UPS.',
            'status' => 'open',
            'status_label' => 'Open',
            'officer' => 'Siti Rahma',
            'has_photo' => false,
            'photo_label' => '',
        ],
        [
            'id' => 'mobile-finding-3',
            'machine' => 'X-Ray Baggage HI-Scan 100100T',
            'machine_key' => 'baggage',
            'location' => 'Terminal 1',
            'date' => '2026-10-09',
            'date_label' => '09 Okt 2026',
            'item' => 'Conveyor belt tidak stabil',
            'criteria' => 'Conveyor bergerak stabil, lurus, dan tidak tersendat.',
            'description' => 'Belt sempat bergerak miring ketika membawa beban uji.',
            'follow_up' => 'Perlu pengecekan motor conveyor.',
            'status' => 'resolved',
            'status_label' => 'Resolved',
            'officer' => 'Budi Santoso',
            'has_photo' => true,
            'photo_label' => 'Foto conveyor belt',
        ],
        [
            'id' => 'mobile-finding-4',
            'machine' => 'X-Ray Cabin',
            'machine_key' => 'cabin',
            'location' => 'Terminal 2',
            'date' => '2026-10-08',
            'date_label' => '08 Okt 2026',
            'item' => 'Lead curtain mulai sobek',
            'criteria' => 'Lead curtain utuh dan menutup jalur pemeriksaan.',
            'description' => 'Terdapat sobekan kecil pada sisi bawah curtain keluar.',
            'follow_up' => 'Ajukan penggantian lead curtain.',
            'status' => 'open',
            'status_label' => 'Open',
            'officer' => 'Andi Pratama',
            'has_photo' => true,
            'photo_label' => 'Foto lead curtain',
        ],
        [
            'id' => 'mobile-finding-5',
            'machine' => 'X-Ray Cargo',
            'machine_key' => 'cargo',
            'location' => 'Area Cargo',
            'date' => '2026-10-07',
            'date_label' => '07 Okt 2026',
            'item' => 'Emergency stop terlambat merespons',
            'criteria' => 'Unit berhenti seketika ketika emergency stop ditekan.',
            'description' => 'Terjadi jeda respons pada pengujian pertama.',
            'follow_up' => 'Bersihkan kontak dan lakukan pengujian ulang.',
            'status' => 'review',
            'status_label' => 'Review',
            'officer' => 'Siti Rahma',
            'has_photo' => false,
            'photo_label' => '',
        ],
        [
            'id' => 'mobile-finding-6',
            'machine' => 'X-Ray Baggage HI-Scan 100100T',
            'machine_key' => 'baggage',
            'location' => 'Terminal 1',
            'date' => '2026-10-06',
            'date_label' => '06 Okt 2026',
            'item' => 'Monitor menampilkan citra buram',
            'criteria' => 'Citra objek tampil tajam, stabil, dan mudah diidentifikasi.',
            'description' => 'Citra buram pada sisi kanan monitor saat pemeriksaan.',
            'follow_up' => 'Konektor display dibersihkan dan diuji ulang.',
            'status' => 'resolved',
            'status_label' => 'Resolved',
            'officer' => 'Budi Santoso',
            'has_photo' => true,
            'photo_label' => 'Foto monitor',
        ],
    ];
@endphp

<div class="mobile-findings" data-mobile-findings>
    <header class="mobile-findings-header">
        <span class="mobile-findings-header__icon" aria-hidden="true"><i data-lucide="triangle-alert"></i></span>
        <div>
            <p>Monitoring Maintenance</p>
            <h1>Temuan</h1>
            <span>Pantau temuan hasil pemeriksaan</span>
        </div>
    </header>

    <section class="mobile-findings-filter" aria-labelledby="mobile-findings-filter-title">
        <header>
            <span aria-hidden="true"><i data-lucide="list-filter"></i></span>
            <div><h2 id="mobile-findings-filter-title">Filter Temuan</h2><p>Temukan data yang perlu ditindaklanjuti.</p></div>
        </header>

        <form data-mobile-findings-filter>
            <label>
                <span>Mesin</span>
                <select name="machine">
                    <option value="">Semua mesin</option>
                    <option value="cabin">X-Ray Cabin</option>
                    <option value="cargo">X-Ray Cargo</option>
                    <option value="baggage">X-Ray Baggage HI-Scan 100100T</option>
                </select>
            </label>
            <label>
                <span>Status</span>
                <select name="status">
                    <option value="">Semua status</option>
                    <option value="open">Open</option>
                    <option value="review">Review</option>
                    <option value="resolved">Resolved</option>
                </select>
            </label>
            <label>
                <span>Tanggal</span>
                <input type="date" name="date">
            </label>
            <label class="mobile-findings-filter__search">
                <span>Cari temuan</span>
                <span><i data-lucide="search" aria-hidden="true"></i><input type="search" name="query" placeholder="Cari item atau tindak lanjut..."></span>
            </label>
            <div class="mobile-findings-filter__actions">
                <button type="button" data-mobile-findings-reset><i data-lucide="rotate-ccw" aria-hidden="true"></i>Reset</button>
                <button type="submit"><i data-lucide="check" aria-hidden="true"></i>Terapkan</button>
            </div>
        </form>
    </section>

    <section class="mobile-findings-summary" aria-label="Ringkasan temuan">
        <article class="mobile-findings-summary__card mobile-findings-summary__card--total">
            <span aria-hidden="true"><i data-lucide="clipboard-list"></i></span>
            <div><small>Total Temuan</small><strong data-mobile-findings-summary="total">6</strong></div>
        </article>
        <article class="mobile-findings-summary__card mobile-findings-summary__card--open">
            <span aria-hidden="true"><i data-lucide="circle-alert"></i></span>
            <div><small>Open</small><strong data-mobile-findings-summary="open">2</strong></div>
        </article>
        <article class="mobile-findings-summary__card mobile-findings-summary__card--review">
            <span aria-hidden="true"><i data-lucide="search-check"></i></span>
            <div><small>Review</small><strong data-mobile-findings-summary="review">2</strong></div>
        </article>
        <article class="mobile-findings-summary__card mobile-findings-summary__card--resolved">
            <span aria-hidden="true"><i data-lucide="circle-check-big"></i></span>
            <div><small>Resolved</small><strong data-mobile-findings-summary="resolved">2</strong></div>
        </article>
    </section>

    <section class="mobile-findings-list-section" aria-labelledby="mobile-findings-list-title">
        <header>
            <div><p>Hasil pemeriksaan</p><h2 id="mobile-findings-list-title">Daftar Temuan</h2></div>
            <span data-mobile-findings-result-count>6 temuan</span>
        </header>

        <div class="mobile-findings-list" data-mobile-findings-list>
            @foreach ($mobileFindings as $finding)
                <article
                    class="mobile-finding-card"
                    data-mobile-finding-card
                    data-id="{{ $finding['id'] }}"
                    data-machine="{{ $finding['machine'] }}"
                    data-machine-key="{{ $finding['machine_key'] }}"
                    data-location="{{ $finding['location'] }}"
                    data-date="{{ $finding['date'] }}"
                    data-date-label="{{ $finding['date_label'] }}"
                    data-item="{{ $finding['item'] }}"
                    data-criteria="{{ $finding['criteria'] }}"
                    data-description="{{ $finding['description'] }}"
                    data-follow-up="{{ $finding['follow_up'] }}"
                    data-status="{{ $finding['status'] }}"
                    data-status-label="{{ $finding['status_label'] }}"
                    data-officer="{{ $finding['officer'] }}"
                    data-has-photo="{{ $finding['has_photo'] ? 'true' : 'false' }}"
                    data-photo-label="{{ $finding['photo_label'] }}"
                >
                    <header class="mobile-finding-card__header">
                        <span class="mobile-finding-card__machine-icon" aria-hidden="true"><i data-lucide="scan-line"></i></span>
                        <div><p data-mobile-finding-card-location>{{ $finding['location'] }}</p><h3 data-mobile-finding-card-machine>{{ $finding['machine'] }}</h3></div>
                        <span class="mobile-finding-badge mobile-finding-badge--{{ $finding['status'] }}" data-mobile-finding-card-status>{{ $finding['status_label'] }}</span>
                    </header>

                    <div class="mobile-finding-card__issue">
                        <span>Item tidak sesuai</span>
                        <strong data-mobile-finding-card-item>{{ $finding['item'] }}</strong>
                    </div>

                    <dl class="mobile-finding-card__details">
                        <div><dt>Keterangan</dt><dd data-mobile-finding-card-description>{{ $finding['description'] }}</dd></div>
                        <div><dt>Tindak lanjut</dt><dd data-mobile-finding-card-follow-up>{{ $finding['follow_up'] }}</dd></div>
                    </dl>

                    <div class="mobile-finding-card__meta">
                        <span><i data-lucide="calendar-days" aria-hidden="true"></i><time datetime="{{ $finding['date'] }}" data-mobile-finding-card-date>{{ $finding['date_label'] }}</time></span>
                        <div data-mobile-finding-card-photo @if (! $finding['has_photo']) hidden @endif>
                            <img src="" alt="" data-mobile-finding-card-photo-image hidden>
                            <span aria-hidden="true"><i data-lucide="image"></i></span>
                            <small>Foto tersedia</small>
                        </div>
                    </div>

                    <footer class="mobile-finding-card__actions">
                        <button type="button" data-mobile-finding-detail data-finding-id="{{ $finding['id'] }}"><i data-lucide="eye" aria-hidden="true"></i>Detail</button>
                        <button type="button" data-mobile-finding-status data-finding-id="{{ $finding['id'] }}"><i data-lucide="refresh-cw" aria-hidden="true"></i>Ubah Status</button>
                    </footer>
                </article>
            @endforeach
        </div>

        <div class="mobile-findings-empty" data-mobile-findings-empty hidden>
            <span aria-hidden="true"><i data-lucide="search-x"></i></span>
            <strong>Temuan tidak ditemukan</strong>
            <p>Coba ubah atau reset filter yang digunakan.</p>
        </div>
    </section>

    <button class="mobile-findings-fab" type="button" data-mobile-finding-create>
        <i data-lucide="plus" aria-hidden="true"></i><span>Tambah Temuan</span>
    </button>

    <div class="mobile-finding-sheet" data-mobile-finding-sheet="detail" aria-hidden="true" hidden>
        <button class="mobile-finding-sheet__backdrop" type="button" data-mobile-finding-sheet-close tabindex="-1" aria-label="Tutup detail temuan"></button>
        <section class="mobile-finding-sheet__panel" data-mobile-finding-sheet-panel role="dialog" aria-modal="true" aria-labelledby="mobile-finding-detail-title" tabindex="-1">
            <div class="mobile-finding-sheet__handle" aria-hidden="true"></div>
            <header class="mobile-finding-sheet__header">
                <span aria-hidden="true"><i data-lucide="clipboard-search"></i></span>
                <div><p>Informasi pemeriksaan</p><h2 id="mobile-finding-detail-title">Detail Temuan</h2></div>
                <button type="button" data-mobile-finding-sheet-close aria-label="Tutup detail"><i data-lucide="x" aria-hidden="true"></i></button>
            </header>

            <div class="mobile-finding-detail">
                <div class="mobile-finding-detail__hero">
                    <div><small data-mobile-finding-detail-field="location">Lokasi</small><h3 data-mobile-finding-detail-field="item">Item tidak sesuai</h3><p data-mobile-finding-detail-field="machine">Nama mesin</p></div>
                    <span class="mobile-finding-badge" data-mobile-finding-detail-field="statusLabel">Status</span>
                </div>

                <dl class="mobile-finding-detail__grid">
                    <div><dt>Tanggal temuan</dt><dd data-mobile-finding-detail-field="dateLabel">-</dd></div>
                    <div><dt>Petugas</dt><dd data-mobile-finding-detail-field="officer">-</dd></div>
                    <div class="mobile-finding-detail__wide"><dt>Kriteria pemeriksaan</dt><dd data-mobile-finding-detail-field="criteria">-</dd></div>
                    <div class="mobile-finding-detail__wide"><dt>Keterangan temuan</dt><dd data-mobile-finding-detail-field="description">-</dd></div>
                    <div class="mobile-finding-detail__wide"><dt>Tindak lanjut</dt><dd data-mobile-finding-detail-field="followUp">-</dd></div>
                </dl>

                <div class="mobile-finding-detail__photo" data-mobile-finding-detail-photo>
                    <div data-mobile-finding-detail-photo-placeholder><i data-lucide="image" aria-hidden="true"></i><span>Foto dokumentasi</span></div>
                    <img src="" alt="Foto dokumentasi temuan" data-mobile-finding-detail-photo-image hidden>
                    <small data-mobile-finding-detail-field="photoLabel">Foto temuan</small>
                </div>
                <div class="mobile-finding-detail__photo-empty" data-mobile-finding-detail-photo-empty hidden><i data-lucide="image-off" aria-hidden="true"></i><span>Foto temuan tidak tersedia.</span></div>
            </div>

            <footer class="mobile-finding-sheet__footer">
                <button type="button" class="mobile-finding-button mobile-finding-button--soft" data-mobile-finding-edit><i data-lucide="pencil" aria-hidden="true"></i>Edit Temuan</button>
                <button type="button" class="mobile-finding-button mobile-finding-button--primary" data-mobile-finding-sheet-close>Tutup</button>
            </footer>
        </section>
    </div>

    <div class="mobile-finding-sheet" data-mobile-finding-sheet="status" aria-hidden="true" hidden>
        <button class="mobile-finding-sheet__backdrop" type="button" data-mobile-finding-sheet-close tabindex="-1" aria-label="Tutup form status"></button>
        <section class="mobile-finding-sheet__panel mobile-finding-sheet__panel--compact" data-mobile-finding-sheet-panel role="dialog" aria-modal="true" aria-labelledby="mobile-finding-status-title" tabindex="-1">
            <div class="mobile-finding-sheet__handle" aria-hidden="true"></div>
            <header class="mobile-finding-sheet__header">
                <span aria-hidden="true"><i data-lucide="refresh-cw"></i></span>
                <div><p>Perbarui tindak lanjut</p><h2 id="mobile-finding-status-title">Ubah Status</h2></div>
                <button type="button" data-mobile-finding-sheet-close aria-label="Tutup form status"><i data-lucide="x" aria-hidden="true"></i></button>
            </header>
            <form class="mobile-finding-status-form" data-mobile-finding-status-form>
                <p data-mobile-finding-status-item>Item temuan</p>
                <label><span>Status temuan</span><select name="status" required><option value="open">Open</option><option value="review">Review</option><option value="resolved">Resolved</option></select></label>
                <div class="mobile-finding-sheet__footer">
                    <button type="button" class="mobile-finding-button mobile-finding-button--soft" data-mobile-finding-sheet-close>Batal</button>
                    <button type="submit" class="mobile-finding-button mobile-finding-button--primary">Simpan Status</button>
                </div>
            </form>
        </section>
    </div>

    <div class="mobile-finding-sheet" data-mobile-finding-sheet="form" aria-hidden="true" hidden>
        <button class="mobile-finding-sheet__backdrop" type="button" data-mobile-finding-sheet-close tabindex="-1" aria-label="Tutup form temuan"></button>
        <section class="mobile-finding-sheet__panel" data-mobile-finding-sheet-panel role="dialog" aria-modal="true" aria-labelledby="mobile-finding-form-title" tabindex="-1">
            <div class="mobile-finding-sheet__handle" aria-hidden="true"></div>
            <header class="mobile-finding-sheet__header">
                <span aria-hidden="true"><i data-lucide="file-pen-line"></i></span>
                <div><p>Data temuan pemeriksaan</p><h2 id="mobile-finding-form-title" data-mobile-finding-form-title>Tambah Temuan</h2></div>
                <button type="button" data-mobile-finding-sheet-close aria-label="Tutup form temuan"><i data-lucide="x" aria-hidden="true"></i></button>
            </header>

            <form class="mobile-finding-form" data-mobile-finding-form>
                <label><span>Mesin</span><select name="machineKey" required><option value="">Pilih mesin</option><option value="cabin">X-Ray Cabin</option><option value="cargo">X-Ray Cargo</option><option value="baggage">X-Ray Baggage HI-Scan 100100T</option></select></label>
                <label><span>Item tidak sesuai</span><input type="text" name="item" placeholder="Masukkan item pemeriksaan" required></label>
                <label><span>Keterangan temuan</span><textarea name="description" rows="3" placeholder="Jelaskan kondisi yang ditemukan" required></textarea></label>
                <label><span>Tindak lanjut</span><textarea name="followUp" rows="3" placeholder="Jelaskan rencana tindak lanjut" required></textarea></label>
                <label><span>Status</span><select name="status" required><option value="open">Open</option><option value="review">Review</option><option value="resolved">Resolved</option></select></label>
                <label class="mobile-finding-upload">
                    <span>Foto temuan <small>(opsional)</small></span>
                    <input type="file" name="photo" accept="image/png,image/jpeg,image/webp" aria-describedby="mobile-finding-photo-help mobile-finding-photo-error" data-mobile-finding-photo-input>
                    <span class="mobile-finding-upload__surface">
                        <span data-mobile-finding-photo-placeholder><i data-lucide="image-up" aria-hidden="true"></i><strong>Pilih foto</strong><small id="mobile-finding-photo-help">JPG, PNG, atau WEBP</small></span>
                        <img src="" alt="Preview foto temuan" data-mobile-finding-photo-preview hidden>
                    </span>
                </label>
                <p class="mobile-finding-form__error" id="mobile-finding-photo-error" role="alert" data-mobile-finding-photo-error hidden></p>
                <div class="mobile-finding-sheet__footer">
                    <button type="button" class="mobile-finding-button mobile-finding-button--soft" data-mobile-finding-sheet-close>Batal</button>
                    <button type="submit" class="mobile-finding-button mobile-finding-button--primary"><i data-lucide="save" aria-hidden="true"></i>Simpan</button>
                </div>
            </form>
        </section>
    </div>

    <div class="mobile-findings-toast" role="status" aria-live="polite" data-mobile-findings-toast hidden>
        <span aria-hidden="true"><i data-lucide="circle-check"></i></span>
        <p data-mobile-findings-toast-message>Perubahan berhasil disimpan.</p>
        <button type="button" data-mobile-findings-toast-close aria-label="Tutup notifikasi"><i data-lucide="x" aria-hidden="true"></i></button>
    </div>
</div>
