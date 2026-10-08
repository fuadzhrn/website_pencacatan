@extends('layouts.app')

@section('title', 'Master Checklist')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/checklists.css') }}">
@endpush

@php
    $checklistItems = [
        [
            'category' => 'Harian',
            'group' => 'Safety Check',
            'item' => 'Pemeriksaan lead curtain',
            'criteria' => 'Tidak sobek',
            'frequency' => 'daily',
            'frequency_label' => 'Harian',
            'input_type' => 'checklist',
            'input_label' => 'Sesuai / Tidak Sesuai',
            'status' => 'Aktif',
            'note' => 'Pastikan seluruh bagian tirai timbal diperiksa dari sisi masuk dan keluar.',
        ],
        [
            'category' => 'Harian',
            'group' => 'Safety Check',
            'item' => 'Pemeriksaan conveyor belt',
            'criteria' => 'Tidak sobek',
            'frequency' => 'daily',
            'frequency_label' => 'Harian',
            'input_type' => 'checklist',
            'input_label' => 'Sesuai / Tidak Sesuai',
            'status' => 'Aktif',
            'note' => 'Periksa permukaan dan kestabilan pergerakan conveyor belt.',
        ],
        [
            'category' => 'Harian',
            'group' => 'Safety Check',
            'item' => 'Leakage Radiation Test',
            'criteria' => 'Maksimal 1 uSv/jam pada jarak 10 cm dari permukaan mesin X-Ray',
            'frequency' => 'daily',
            'frequency_label' => 'Harian',
            'input_type' => 'numeric_with_note',
            'input_label' => 'Nilai Aktual + Catatan',
            'status' => 'Aktif',
            'note' => 'Catat nilai hasil pengukuran dan titik pengambilan sampel.',
        ],
        [
            'category' => 'Harian',
            'group' => 'Pembersihan',
            'item' => 'Unit bagian luar',
            'criteria' => 'Bersih',
            'frequency' => 'daily',
            'frequency_label' => 'Harian',
            'input_type' => 'checklist',
            'input_label' => 'Sesuai / Tidak Sesuai',
            'status' => 'Aktif',
            'note' => 'Gunakan bahan pembersih yang tidak merusak permukaan unit.',
        ],
        [
            'category' => 'Mingguan',
            'group' => 'Pemeriksaan Mingguan',
            'item' => 'Pembersihan dan pemeriksaan light barriers',
            'criteria' => 'Bersih',
            'frequency' => 'weekly',
            'frequency_label' => 'Mingguan',
            'input_type' => 'checklist',
            'input_label' => 'Sesuai / Tidak Sesuai',
            'status' => 'Aktif',
            'note' => 'Pastikan sensor light barriers merespons tanpa hambatan.',
        ],
        [
            'category' => 'Bulanan',
            'group' => 'Functional Test',
            'item' => 'Zoom-in / Zoom-out',
            'criteria' => 'Berfungsi',
            'frequency' => 'monthly',
            'frequency_label' => 'Bulanan',
            'input_type' => 'checklist',
            'input_label' => 'Sesuai / Tidak Sesuai',
            'status' => 'Aktif',
            'note' => 'Uji fungsi zoom pada beberapa tingkat pembesaran citra.',
        ],
        [
            'category' => 'Triwulan',
            'group' => 'Unit Configuration',
            'item' => 'Pemeriksaan pengaturan tanggal, bulan, tahun, dan image orientation',
            'criteria' => 'Sesuai kebutuhan operasional',
            'frequency' => 'quarterly',
            'frequency_label' => 'Triwulan',
            'input_type' => 'note',
            'input_label' => 'Catatan',
            'status' => 'Aktif',
            'note' => 'Dokumentasikan perubahan konfigurasi apabila dilakukan penyesuaian.',
        ],
        [
            'category' => 'Semesteran',
            'group' => 'Pemeriksaan Semesteran',
            'item' => 'Pemeriksaan X-Ray beam alignment',
            'criteria' => 'Signal',
            'frequency' => 'semester',
            'frequency_label' => 'Semesteran',
            'input_type' => 'numeric_with_note',
            'input_label' => 'Nilai Aktual + Catatan',
            'status' => 'Aktif',
            'note' => 'Rekam hasil pengukuran signal dan catat penyimpangan yang ditemukan.',
        ],
        [
            'category' => 'Tahunan',
            'group' => 'Pemeriksaan Tahunan',
            'item' => 'Pemeriksaan drum motor',
            'criteria' => 'Tidak bunyi dan tidak bocor oli',
            'frequency' => 'yearly',
            'frequency_label' => 'Tahunan',
            'input_type' => 'checklist_with_note',
            'input_label' => 'Sesuai / Tidak Sesuai + Catatan',
            'status' => 'Aktif',
            'note' => 'Catat kondisi suara motor dan indikasi kebocoran setelah unit dijalankan.',
        ],
    ];
@endphp

@section('content')
    <div class="checklists-page">
        <header class="checklists-page-header">
            <div>
                <p class="checklists-page-header__eyebrow">Konfigurasi Pemeriksaan</p>
                <h2>Master Checklist</h2>
                <p>Kelola kategori dan item pemeriksaan preventive maintenance mesin X-Ray.</p>
            </div>
            <button class="button button--primary" type="button" data-checklist-create>
                <i data-lucide="plus" aria-hidden="true"></i>
                Tambah Item Checklist
            </button>
        </header>

        <section class="checklists-summary" aria-label="Ringkasan master checklist">
            <article class="checklists-summary-card checklists-summary-card--total">
                <span class="checklists-summary-card__icon" aria-hidden="true">
                    <i data-lucide="list-checks"></i>
                </span>
                <div>
                    <p>Total Item</p>
                    <strong>9</strong>
                    <span>Seluruh item pemeriksaan</span>
                </div>
            </article>

            <article class="checklists-summary-card checklists-summary-card--daily">
                <span class="checklists-summary-card__icon" aria-hidden="true">
                    <i data-lucide="calendar-check-2"></i>
                </span>
                <div>
                    <p>Item Harian</p>
                    <strong>4</strong>
                    <span>Pemeriksaan setiap hari</span>
                </div>
            </article>

            <article class="checklists-summary-card checklists-summary-card--periodic">
                <span class="checklists-summary-card__icon" aria-hidden="true">
                    <i data-lucide="calendar-range"></i>
                </span>
                <div>
                    <p>Mingguan / Bulanan</p>
                    <strong>2</strong>
                    <span>Item pemeriksaan berkala</span>
                </div>
            </article>

            <article class="checklists-summary-card checklists-summary-card--inactive">
                <span class="checklists-summary-card__icon" aria-hidden="true">
                    <i data-lucide="circle-off"></i>
                </span>
                <div>
                    <p>Item Nonaktif</p>
                    <strong>0</strong>
                    <span>Tidak ada item nonaktif</span>
                </div>
            </article>
        </section>

        <section class="checklists-filter" aria-labelledby="checklist-filter-title">
            <div class="checklists-filter__heading">
                <span aria-hidden="true"><i data-lucide="list-filter"></i></span>
                <div>
                    <h2 id="checklist-filter-title">Filter Kategori</h2>
                    <p>Pilih periode pemeriksaan untuk menyaring daftar.</p>
                </div>
            </div>
            <div class="checklists-tabs" role="tablist" aria-label="Filter kategori pemeriksaan">
                <button class="checklists-tab is-active" type="button" role="tab" aria-selected="true" data-category-filter="Semua">Semua <span>9</span></button>
                <button class="checklists-tab" type="button" role="tab" aria-selected="false" data-category-filter="Harian">Harian <span>4</span></button>
                <button class="checklists-tab" type="button" role="tab" aria-selected="false" data-category-filter="Mingguan">Mingguan <span>1</span></button>
                <button class="checklists-tab" type="button" role="tab" aria-selected="false" data-category-filter="Bulanan">Bulanan <span>1</span></button>
                <button class="checklists-tab" type="button" role="tab" aria-selected="false" data-category-filter="Triwulan">Triwulan <span>1</span></button>
                <button class="checklists-tab" type="button" role="tab" aria-selected="false" data-category-filter="Semesteran">Semesteran <span>1</span></button>
                <button class="checklists-tab" type="button" role="tab" aria-selected="false" data-category-filter="Tahunan">Tahunan <span>1</span></button>
            </div>
        </section>

        <section class="checklists-table-panel" aria-labelledby="checklists-table-title">
            <header class="checklists-table-panel__header">
                <div>
                    <h2 id="checklists-table-title">Daftar Item Pemeriksaan</h2>
                    <p>Item aktif yang digunakan sebagai acuan preventive maintenance.</p>
                </div>
                <span class="checklists-table-panel__count" data-checklist-count>9 data</span>
            </header>

            <div class="checklists-table-wrap">
                <table class="checklists-table">
                    <thead>
                        <tr>
                            <th scope="col">No</th>
                            <th scope="col">Kategori</th>
                            <th scope="col">Kelompok</th>
                            <th scope="col">Item Pemeriksaan</th>
                            <th scope="col">Kriteria / Standar</th>
                            <th scope="col">Frekuensi</th>
                            <th scope="col">Tipe Input</th>
                            <th scope="col">Status</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($checklistItems as $index => $checklist)
                            @php
                                $categoryClass = strtolower($checklist['category']);
                                $inputClass = str_contains($checklist['input_type'], 'numeric')
                                    ? 'numeric'
                                    : (str_contains($checklist['input_type'], 'note') ? 'note' : 'checklist');
                            @endphp
                            <tr data-checklist-row data-category="{{ $checklist['category'] }}">
                                <td><span class="checklists-table__number">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span></td>
                                <td><span class="checklist-category checklist-category--{{ $categoryClass }}">{{ $checklist['category'] }}</span></td>
                                <td>{{ $checklist['group'] }}</td>
                                <td>
                                    <div class="checklist-item-name">
                                        <span aria-hidden="true"><i data-lucide="clipboard-check"></i></span>
                                        <strong>{{ $checklist['item'] }}</strong>
                                    </div>
                                </td>
                                <td><p class="checklist-criteria">{{ $checklist['criteria'] }}</p></td>
                                <td>
                                    <span class="checklist-frequency">
                                        {{ $checklist['frequency_label'] }}
                                        <small>{{ $checklist['frequency'] }}</small>
                                    </span>
                                </td>
                                <td><span class="checklist-input checklist-input--{{ $inputClass }}">{{ $checklist['input_label'] }}</span></td>
                                <td><span class="checklist-status checklist-status--active">{{ $checklist['status'] }}</span></td>
                                <td>
                                    <div class="checklists-actions">
                                        <button
                                            class="checklist-action checklist-action--detail"
                                            type="button"
                                            title="Detail checklist"
                                            data-checklist-detail
                                            data-category="{{ $checklist['category'] }}"
                                            data-group="{{ $checklist['group'] }}"
                                            data-item="{{ $checklist['item'] }}"
                                            data-criteria="{{ $checklist['criteria'] }}"
                                            data-frequency="{{ $checklist['frequency'] }}"
                                            data-frequency-label="{{ $checklist['frequency_label'] }}"
                                            data-input-type="{{ $checklist['input_type'] }}"
                                            data-input-label="{{ $checklist['input_label'] }}"
                                            data-status="{{ $checklist['status'] }}"
                                            data-note="{{ $checklist['note'] }}"
                                        >
                                            <i data-lucide="eye" aria-hidden="true"></i>
                                            Detail
                                        </button>
                                        <button
                                            class="checklist-action checklist-action--edit"
                                            type="button"
                                            title="Edit checklist"
                                            data-checklist-edit
                                            data-category="{{ $checklist['category'] }}"
                                            data-group="{{ $checklist['group'] }}"
                                            data-item="{{ $checklist['item'] }}"
                                            data-criteria="{{ $checklist['criteria'] }}"
                                            data-frequency="{{ $checklist['frequency'] }}"
                                            data-input-type="{{ $checklist['input_type'] }}"
                                            data-status="{{ $checklist['status'] }}"
                                        >
                                            <i data-lucide="pencil" aria-hidden="true"></i>
                                            Edit
                                        </button>
                                        <button class="checklist-action checklist-action--delete" type="button" title="Hapus checklist">
                                            <i data-lucide="trash-2" aria-hidden="true"></i>
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        <tr class="checklists-empty-row" data-checklist-empty hidden>
                            <td colspan="9">
                                <div>
                                    <i data-lucide="clipboard-x" aria-hidden="true"></i>
                                    <strong>Tidak ada item pada kategori ini</strong>
                                    <span>Pilih kategori lain untuk melihat item pemeriksaan.</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <footer class="checklists-table-panel__footer">
                <span data-checklist-range>Menampilkan 1&ndash;9 dari 9 item</span>
                <span>Data dummy &bull; Belum terhubung database</span>
            </footer>
        </section>
    </div>

    <dialog class="checklist-dialog" id="checklist-form-dialog" aria-labelledby="checklist-form-title">
        <form class="checklist-form" id="checklist-form">
            <header class="checklist-dialog__header">
                <div class="checklist-dialog__title">
                    <span aria-hidden="true"><i data-lucide="clipboard-plus"></i></span>
                    <div>
                        <p>Konfigurasi pemeriksaan</p>
                        <h2 id="checklist-form-title">Tambah Item Checklist</h2>
                    </div>
                </div>
                <button class="checklist-dialog__close" type="button" data-dialog-close aria-label="Tutup form checklist">
                    <i data-lucide="x" aria-hidden="true"></i>
                </button>
            </header>

            <div class="checklist-form__body">
                <div class="checklist-form__grid">
                    <div class="form-field">
                        <label for="checklist-category">Kategori pemeriksaan</label>
                        <select class="form-control" id="checklist-category" name="category" required>
                            <option value="Harian">Harian</option>
                            <option value="Mingguan">Mingguan</option>
                            <option value="Bulanan">Bulanan</option>
                            <option value="Triwulan">Triwulan</option>
                            <option value="Semesteran">Semesteran</option>
                            <option value="Tahunan">Tahunan</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="checklist-group">Kelompok pemeriksaan</label>
                        <input class="form-control" id="checklist-group" name="group" type="text" placeholder="Contoh: Safety Check" required>
                    </div>

                    <div class="form-field checklist-form__field--wide">
                        <label for="checklist-item">Nama item pemeriksaan</label>
                        <input class="form-control" id="checklist-item" name="item" type="text" placeholder="Contoh: Pemeriksaan lead curtain" required>
                    </div>

                    <div class="form-field checklist-form__field--wide">
                        <label for="checklist-criteria">Kriteria / standar pemeriksaan</label>
                        <textarea class="form-control" id="checklist-criteria" name="criteria" rows="3" placeholder="Tuliskan standar hasil pemeriksaan" required></textarea>
                    </div>

                    <div class="form-field">
                        <label for="checklist-frequency">Frekuensi pemeriksaan</label>
                        <select class="form-control" id="checklist-frequency" name="frequency" required>
                            <option value="daily">daily</option>
                            <option value="weekly">weekly</option>
                            <option value="monthly">monthly</option>
                            <option value="quarterly">quarterly</option>
                            <option value="semester">semester</option>
                            <option value="yearly">yearly</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="checklist-input-type">Tipe input</label>
                        <select class="form-control" id="checklist-input-type" name="input_type" required>
                            <option value="checklist">checklist</option>
                            <option value="numeric">numeric</option>
                            <option value="note">note</option>
                            <option value="checklist_with_note">checklist_with_note</option>
                            <option value="numeric_with_note">numeric_with_note</option>
                            <option value="checklist_with_photo">checklist_with_photo</option>
                        </select>
                    </div>

                    <div class="form-field checklist-form__field--wide">
                        <label for="checklist-status">Status</label>
                        <select class="form-control" id="checklist-status" name="status" required>
                            <option value="Aktif">Aktif</option>
                            <option value="Nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>
            </div>

            <footer class="checklist-dialog__footer">
                <p><i data-lucide="info" aria-hidden="true"></i> Form masih menggunakan data statis.</p>
                <div>
                    <button class="button button--neutral" type="button" data-dialog-close>Batal</button>
                    <button class="button button--primary" type="submit">
                        <i data-lucide="save" aria-hidden="true"></i>
                        Simpan Item
                    </button>
                </div>
            </footer>
        </form>
    </dialog>

    <dialog class="checklist-dialog checklist-dialog--detail" id="checklist-detail-dialog" aria-labelledby="checklist-detail-title">
        <div class="checklist-detail">
            <header class="checklist-dialog__header">
                <div class="checklist-dialog__title">
                    <span aria-hidden="true"><i data-lucide="clipboard-list"></i></span>
                    <div>
                        <p>Informasi item pemeriksaan</p>
                        <h2 id="checklist-detail-title">Detail Checklist</h2>
                    </div>
                </div>
                <button class="checklist-dialog__close" type="button" data-dialog-close aria-label="Tutup detail checklist">
                    <i data-lucide="x" aria-hidden="true"></i>
                </button>
            </header>

            <div class="checklist-detail__hero">
                <span aria-hidden="true"><i data-lucide="clipboard-check"></i></span>
                <div>
                    <p data-detail-field="group">Kelompok pemeriksaan</p>
                    <h3 data-detail-field="item">Item pemeriksaan</h3>
                    <span class="checklist-category checklist-category--harian" data-detail-category>Harian</span>
                </div>
                <span class="checklist-status checklist-status--active" data-detail-status>Aktif</span>
            </div>

            <dl class="checklist-detail__grid">
                <div class="checklist-detail__wide">
                    <dt>Kriteria / standar</dt>
                    <dd data-detail-field="criteria">-</dd>
                </div>
                <div>
                    <dt>Frekuensi</dt>
                    <dd><span data-detail-field="frequencyLabel">-</span> <code data-detail-field="frequency">-</code></dd>
                </div>
                <div>
                    <dt>Tipe input</dt>
                    <dd><span data-detail-field="inputLabel">-</span> <code data-detail-field="inputType">-</code></dd>
                </div>
                <div class="checklist-detail__wide">
                    <dt>Catatan tambahan</dt>
                    <dd data-detail-field="note">Tidak ada catatan tambahan.</dd>
                </div>
            </dl>

            <footer class="checklist-dialog__footer checklist-dialog__footer--detail">
                <button class="button button--neutral" type="button" data-dialog-close>Tutup</button>
            </footer>
        </div>
    </dialog>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('assets/js/checklists.js') }}"></script>
@endpush
