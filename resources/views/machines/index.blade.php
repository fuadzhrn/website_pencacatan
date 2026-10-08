@extends('layouts.app')

@section('title', 'Data Mesin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/machines.css') }}">
@endpush

@section('content')
    <div class="machines-page">
        <header class="machines-page-header">
            <div>
                <p class="machines-page-header__eyebrow">Master Data Peralatan</p>
                <h2>Data Mesin</h2>
                <p>Kelola data mesin/peralatan X-Ray yang digunakan untuk preventive maintenance.</p>
            </div>
            <button class="button button--primary" type="button" data-machine-create>
                <i data-lucide="plus" aria-hidden="true"></i>
                Tambah Mesin
            </button>
        </header>

        <section class="machines-summary" aria-label="Ringkasan data mesin">
            <article class="machines-summary-card machines-summary-card--total">
                <span class="machines-summary-card__icon" aria-hidden="true">
                    <i data-lucide="scan-line"></i>
                </span>
                <div>
                    <p>Total Mesin</p>
                    <strong>3</strong>
                    <span>Seluruh peralatan tercatat</span>
                </div>
            </article>

            <article class="machines-summary-card machines-summary-card--active">
                <span class="machines-summary-card__icon" aria-hidden="true">
                    <i data-lucide="circle-check-big"></i>
                </span>
                <div>
                    <p>Mesin Aktif</p>
                    <strong>2</strong>
                    <span>Siap digunakan</span>
                </div>
            </article>

            <article class="machines-summary-card machines-summary-card--maintenance">
                <span class="machines-summary-card__icon" aria-hidden="true">
                    <i data-lucide="wrench"></i>
                </span>
                <div>
                    <p>Dalam Maintenance</p>
                    <strong>1</strong>
                    <span>Sedang ditangani</span>
                </div>
            </article>

            <article class="machines-summary-card machines-summary-card--inactive">
                <span class="machines-summary-card__icon" aria-hidden="true">
                    <i data-lucide="circle-off"></i>
                </span>
                <div>
                    <p>Nonaktif</p>
                    <strong>0</strong>
                    <span>Tidak ada mesin nonaktif</span>
                </div>
            </article>
        </section>

        <section class="machines-table-panel" aria-labelledby="machines-table-title">
            <header class="machines-table-panel__header">
                <div>
                    <h2 id="machines-table-title">Daftar Mesin / Peralatan</h2>
                    <p>Data inventaris mesin X-Ray yang terdaftar pada sistem.</p>
                </div>
                <span class="machines-table-panel__count">3 data</span>
            </header>

            <div class="machines-table-wrap">
                <table class="machines-table">
                    <thead>
                        <tr>
                            <th scope="col">No</th>
                            <th scope="col">Nama Peralatan</th>
                            <th scope="col">Merk</th>
                            <th scope="col">Tipe</th>
                            <th scope="col">Serial Number</th>
                            <th scope="col">Lokasi</th>
                            <th scope="col">Bandar Udara</th>
                            <th scope="col">Status</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="machines-table__number">01</span></td>
                            <td>
                                <div class="machines-equipment">
                                    <span aria-hidden="true"><i data-lucide="scan-line"></i></span>
                                    <div>
                                        <strong>Mesin X-Ray Baggage</strong>
                                        <small>Peralatan pemeriksaan bagasi</small>
                                    </div>
                                </div>
                            </td>
                            <td>Smiths Detection</td>
                            <td>HI-Scan 100100T</td>
                            <td><code>XR-100100T-001</code></td>
                            <td>SCP</td>
                            <td>UPBU Malikussaleh</td>
                            <td><span class="machine-status machine-status--active">Aktif</span></td>
                            <td>
                                <div class="machines-actions">
                                    <button
                                        class="machine-action machine-action--detail"
                                        type="button"
                                        title="Detail mesin"
                                        data-machine-detail
                                        data-name="Mesin X-Ray Baggage"
                                        data-brand="Smiths Detection"
                                        data-type="HI-Scan 100100T"
                                        data-serial="XR-100100T-001"
                                        data-location="SCP"
                                        data-airport="UPBU Malikussaleh"
                                        data-status="Aktif"
                                        data-created="12 Januari 2024"
                                        data-inspected="07 Oktober 2026"
                                    >
                                        <i data-lucide="eye" aria-hidden="true"></i>
                                        Detail
                                    </button>
                                    <button
                                        class="machine-action machine-action--edit"
                                        type="button"
                                        title="Edit mesin"
                                        data-machine-edit
                                        data-name="Mesin X-Ray Baggage"
                                        data-brand="Smiths Detection"
                                        data-type="HI-Scan 100100T"
                                        data-serial="XR-100100T-001"
                                        data-location="SCP"
                                        data-airport="UPBU Malikussaleh"
                                        data-status="Aktif"
                                    >
                                        <i data-lucide="pencil" aria-hidden="true"></i>
                                        Edit
                                    </button>
                                    <button class="machine-action machine-action--delete" type="button" title="Hapus mesin">
                                        <i data-lucide="trash-2" aria-hidden="true"></i>
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td><span class="machines-table__number">02</span></td>
                            <td>
                                <div class="machines-equipment">
                                    <span aria-hidden="true"><i data-lucide="scan-line"></i></span>
                                    <div>
                                        <strong>Mesin X-Ray Cabin</strong>
                                        <small>Peralatan pemeriksaan kabin</small>
                                    </div>
                                </div>
                            </td>
                            <td>Smiths Detection</td>
                            <td>HI-Scan 6040i</td>
                            <td><code>XR-6040I-002</code></td>
                            <td>SCP 2</td>
                            <td>UPBU Malikussaleh</td>
                            <td><span class="machine-status machine-status--active">Aktif</span></td>
                            <td>
                                <div class="machines-actions">
                                    <button
                                        class="machine-action machine-action--detail"
                                        type="button"
                                        title="Detail mesin"
                                        data-machine-detail
                                        data-name="Mesin X-Ray Cabin"
                                        data-brand="Smiths Detection"
                                        data-type="HI-Scan 6040i"
                                        data-serial="XR-6040I-002"
                                        data-location="SCP 2"
                                        data-airport="UPBU Malikussaleh"
                                        data-status="Aktif"
                                        data-created="25 Februari 2024"
                                        data-inspected="07 Oktober 2026"
                                    >
                                        <i data-lucide="eye" aria-hidden="true"></i>
                                        Detail
                                    </button>
                                    <button
                                        class="machine-action machine-action--edit"
                                        type="button"
                                        title="Edit mesin"
                                        data-machine-edit
                                        data-name="Mesin X-Ray Cabin"
                                        data-brand="Smiths Detection"
                                        data-type="HI-Scan 6040i"
                                        data-serial="XR-6040I-002"
                                        data-location="SCP 2"
                                        data-airport="UPBU Malikussaleh"
                                        data-status="Aktif"
                                    >
                                        <i data-lucide="pencil" aria-hidden="true"></i>
                                        Edit
                                    </button>
                                    <button class="machine-action machine-action--delete" type="button" title="Hapus mesin">
                                        <i data-lucide="trash-2" aria-hidden="true"></i>
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td><span class="machines-table__number">03</span></td>
                            <td>
                                <div class="machines-equipment">
                                    <span aria-hidden="true"><i data-lucide="scan-line"></i></span>
                                    <div>
                                        <strong>Mesin X-Ray Cargo</strong>
                                        <small>Peralatan pemeriksaan kargo</small>
                                    </div>
                                </div>
                            </td>
                            <td>Rapiscan</td>
                            <td>620XR</td>
                            <td><code>XR-620XR-003</code></td>
                            <td>Cargo Area</td>
                            <td>UPBU Malikussaleh</td>
                            <td><span class="machine-status machine-status--maintenance">Maintenance</span></td>
                            <td>
                                <div class="machines-actions">
                                    <button
                                        class="machine-action machine-action--detail"
                                        type="button"
                                        title="Detail mesin"
                                        data-machine-detail
                                        data-name="Mesin X-Ray Cargo"
                                        data-brand="Rapiscan"
                                        data-type="620XR"
                                        data-serial="XR-620XR-003"
                                        data-location="Cargo Area"
                                        data-airport="UPBU Malikussaleh"
                                        data-status="Maintenance"
                                        data-created="08 Maret 2024"
                                        data-inspected="06 Oktober 2026"
                                    >
                                        <i data-lucide="eye" aria-hidden="true"></i>
                                        Detail
                                    </button>
                                    <button
                                        class="machine-action machine-action--edit"
                                        type="button"
                                        title="Edit mesin"
                                        data-machine-edit
                                        data-name="Mesin X-Ray Cargo"
                                        data-brand="Rapiscan"
                                        data-type="620XR"
                                        data-serial="XR-620XR-003"
                                        data-location="Cargo Area"
                                        data-airport="UPBU Malikussaleh"
                                        data-status="Maintenance"
                                    >
                                        <i data-lucide="pencil" aria-hidden="true"></i>
                                        Edit
                                    </button>
                                    <button class="machine-action machine-action--delete" type="button" title="Hapus mesin">
                                        <i data-lucide="trash-2" aria-hidden="true"></i>
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <footer class="machines-table-panel__footer">
                <span>Menampilkan 1&ndash;3 dari 3 mesin</span>
                <span>Data dummy &bull; Belum terhubung database</span>
            </footer>
        </section>
    </div>

    <dialog class="machine-dialog" id="machine-form-dialog" aria-labelledby="machine-form-title">
        <form class="machine-form" id="machine-form">
            <header class="machine-dialog__header">
                <div class="machine-dialog__title">
                    <span aria-hidden="true"><i data-lucide="scan-line"></i></span>
                    <div>
                        <p>Master data peralatan</p>
                        <h2 id="machine-form-title">Tambah Mesin</h2>
                    </div>
                </div>
                <button class="machine-dialog__close" type="button" data-dialog-close aria-label="Tutup form mesin">
                    <i data-lucide="x" aria-hidden="true"></i>
                </button>
            </header>

            <div class="machine-form__body">
                <div class="machine-form__grid">
                    <div class="form-field machine-form__field--wide">
                        <label for="machine-name">Nama Peralatan</label>
                        <input class="form-control" id="machine-name" name="name" type="text" placeholder="Contoh: Mesin X-Ray Baggage" required>
                    </div>

                    <div class="form-field">
                        <label for="machine-brand">Merk</label>
                        <input class="form-control" id="machine-brand" name="brand" type="text" placeholder="Contoh: Smiths Detection" required>
                    </div>

                    <div class="form-field">
                        <label for="machine-type">Tipe</label>
                        <input class="form-control" id="machine-type" name="type" type="text" placeholder="Contoh: HI-Scan 100100T" required>
                    </div>

                    <div class="form-field">
                        <label for="machine-serial">Serial Number</label>
                        <input class="form-control" id="machine-serial" name="serial" type="text" placeholder="Contoh: XR-100100T-001" required>
                    </div>

                    <div class="form-field">
                        <label for="machine-location">Lokasi</label>
                        <input class="form-control" id="machine-location" name="location" type="text" placeholder="Contoh: SCP" required>
                    </div>

                    <div class="form-field machine-form__field--wide">
                        <label for="machine-airport">Bandar Udara</label>
                        <input class="form-control" id="machine-airport" name="airport" type="text" value="UPBU Malikussaleh" required>
                    </div>

                    <div class="form-field machine-form__field--wide">
                        <label for="machine-status">Status</label>
                        <select class="form-control" id="machine-status" name="status" required>
                            <option value="Aktif">Aktif</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>
            </div>

            <footer class="machine-dialog__footer">
                <p><i data-lucide="info" aria-hidden="true"></i> Form masih menggunakan data statis.</p>
                <div>
                    <button class="button button--neutral" type="button" data-dialog-close>Batal</button>
                    <button class="button button--primary" type="submit">
                        <i data-lucide="save" aria-hidden="true"></i>
                        Simpan Data
                    </button>
                </div>
            </footer>
        </form>
    </dialog>

    <dialog class="machine-dialog machine-dialog--detail" id="machine-detail-dialog" aria-labelledby="machine-detail-title">
        <div class="machine-detail">
            <header class="machine-dialog__header">
                <div class="machine-dialog__title">
                    <span aria-hidden="true"><i data-lucide="scan-search"></i></span>
                    <div>
                        <p>Informasi peralatan</p>
                        <h2 id="machine-detail-title">Detail Mesin</h2>
                    </div>
                </div>
                <button class="machine-dialog__close" type="button" data-dialog-close aria-label="Tutup detail mesin">
                    <i data-lucide="x" aria-hidden="true"></i>
                </button>
            </header>

            <div class="machine-detail__hero">
                <span aria-hidden="true"><i data-lucide="scan-line"></i></span>
                <div>
                    <p>Nama peralatan</p>
                    <h3 data-detail-field="name">Mesin X-Ray</h3>
                    <code data-detail-field="serial">-</code>
                </div>
                <span class="machine-status machine-status--active" data-detail-status>Aktif</span>
            </div>

            <dl class="machine-detail__grid">
                <div>
                    <dt>Merk</dt>
                    <dd data-detail-field="brand">-</dd>
                </div>
                <div>
                    <dt>Tipe</dt>
                    <dd data-detail-field="type">-</dd>
                </div>
                <div>
                    <dt>Lokasi</dt>
                    <dd data-detail-field="location">-</dd>
                </div>
                <div>
                    <dt>Bandar Udara</dt>
                    <dd data-detail-field="airport">-</dd>
                </div>
                <div>
                    <dt>Tanggal dibuat</dt>
                    <dd data-detail-field="created">-</dd>
                </div>
                <div>
                    <dt>Terakhir diperiksa</dt>
                    <dd data-detail-field="inspected">-</dd>
                </div>
            </dl>

            <footer class="machine-dialog__footer machine-dialog__footer--detail">
                <button class="button button--neutral" type="button" data-dialog-close>Tutup</button>
            </footer>
        </div>
    </dialog>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('assets/js/machines.js') }}"></script>
@endpush
