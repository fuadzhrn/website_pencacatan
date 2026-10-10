@php
    $mobileChecklistCategories = [
        ['key' => 'daily', 'label' => 'Harian', 'icon' => 'calendar-check-2'],
        ['key' => 'weekly', 'label' => 'Mingguan', 'icon' => 'calendar-days'],
        ['key' => 'monthly', 'label' => 'Bulanan', 'icon' => 'calendar-range'],
        ['key' => 'quarterly', 'label' => 'Triwulan', 'icon' => 'calendar-sync'],
        ['key' => 'semester', 'label' => 'Semesteran', 'icon' => 'calendar-cog'],
        ['key' => 'yearly', 'label' => 'Tahunan', 'icon' => 'calendar-clock'],
    ];

    $mobileChecklistItems = [
        [
            'id' => 'checklist-1',
            'name' => 'Lead curtain dalam kondisi baik',
            'category' => 'daily',
            'category_label' => 'Harian',
            'frequency' => 'daily',
            'frequency_label' => 'Harian',
            'criteria' => 'Tidak robek dan menutup dengan baik',
            'input_type' => 'boolean',
            'input_label' => 'Sesuai/Tidak Sesuai',
            'status' => 'active',
            'status_label' => 'Aktif',
            'note' => 'Periksa seluruh bagian tirai timbal pada sisi masuk dan keluar mesin.',
        ],
        [
            'id' => 'checklist-2',
            'name' => 'Conveyor belt berjalan normal',
            'category' => 'daily',
            'category_label' => 'Harian',
            'frequency' => 'daily',
            'frequency_label' => 'Harian',
            'criteria' => 'Tidak tersendat dan tidak miring',
            'input_type' => 'boolean',
            'input_label' => 'Sesuai/Tidak Sesuai',
            'status' => 'active',
            'status_label' => 'Aktif',
            'note' => 'Amati kestabilan pergerakan dan posisi conveyor saat mesin beroperasi.',
        ],
        [
            'id' => 'checklist-3',
            'name' => 'Monitor menampilkan gambar jelas',
            'category' => 'weekly',
            'category_label' => 'Mingguan',
            'frequency' => 'weekly',
            'frequency_label' => 'Mingguan',
            'criteria' => 'Tidak buram dan tidak berkedip',
            'input_type' => 'note',
            'input_label' => 'Catatan',
            'status' => 'active',
            'status_label' => 'Aktif',
            'note' => 'Periksa kualitas gambar pada monitor operator dengan beberapa objek uji.',
        ],
        [
            'id' => 'checklist-4',
            'name' => 'Light barriers merespons objek',
            'category' => 'weekly',
            'category_label' => 'Mingguan',
            'frequency' => 'weekly',
            'frequency_label' => 'Mingguan',
            'criteria' => 'Sensor merespons tanpa hambatan',
            'input_type' => 'boolean',
            'input_label' => 'Sesuai/Tidak Sesuai',
            'status' => 'inactive',
            'status_label' => 'Nonaktif',
            'note' => 'Item dinonaktifkan sementara selama penyesuaian prosedur pemeriksaan.',
        ],
        [
            'id' => 'checklist-5',
            'name' => 'UPS dalam kondisi normal',
            'category' => 'monthly',
            'category_label' => 'Bulanan',
            'frequency' => 'monthly',
            'frequency_label' => 'Bulanan',
            'criteria' => 'Backup daya bekerja baik',
            'input_type' => 'boolean',
            'input_label' => 'Sesuai/Tidak Sesuai',
            'status' => 'active',
            'status_label' => 'Aktif',
            'note' => 'Uji perpindahan sumber daya dan catat durasi backup UPS.',
        ],
        [
            'id' => 'checklist-6',
            'name' => 'Image orientation sesuai konfigurasi',
            'category' => 'quarterly',
            'category_label' => 'Triwulan',
            'frequency' => 'quarterly',
            'frequency_label' => 'Triwulan',
            'criteria' => 'Sesuai kebutuhan operasional',
            'input_type' => 'note',
            'input_label' => 'Catatan',
            'status' => 'active',
            'status_label' => 'Aktif',
            'note' => 'Dokumentasikan perubahan konfigurasi apabila dilakukan penyesuaian.',
        ],
        [
            'id' => 'checklist-7',
            'name' => 'X-Ray beam alignment stabil',
            'category' => 'semester',
            'category_label' => 'Semesteran',
            'frequency' => 'semester',
            'frequency_label' => 'Semesteran',
            'criteria' => 'Signal berada dalam batas standar',
            'input_type' => 'numeric',
            'input_label' => 'Nilai + Catatan',
            'status' => 'active',
            'status_label' => 'Aktif',
            'note' => 'Rekam hasil pengukuran signal dan setiap penyimpangan yang ditemukan.',
        ],
        [
            'id' => 'checklist-8',
            'name' => 'Drum motor beroperasi normal',
            'category' => 'yearly',
            'category_label' => 'Tahunan',
            'frequency' => 'yearly',
            'frequency_label' => 'Tahunan',
            'criteria' => 'Tidak berbunyi dan tidak bocor oli',
            'input_type' => 'boolean_note',
            'input_label' => 'Sesuai/Tidak Sesuai + Catatan',
            'status' => 'active',
            'status_label' => 'Aktif',
            'note' => 'Jalankan unit dan amati suara motor serta indikasi kebocoran oli.',
        ],
    ];
@endphp

<main class="mobile-checklists" data-mobile-checklists>
    <header class="mobile-checklists-header">
        <span class="mobile-checklists-header__icon" aria-hidden="true">
            <i data-lucide="list-checks"></i>
        </span>
        <div>
            <p>Konfigurasi pemeriksaan</p>
            <h1>Master Checklist</h1>
            <span>Kelola kategori dan item pemeriksaan</span>
        </div>
    </header>

    <section class="mobile-checklists-filter" aria-labelledby="mobile-checklists-filter-title">
        <header class="mobile-checklists-section-heading">
            <div>
                <p>Pencarian ringkas</p>
                <h2 id="mobile-checklists-filter-title">Filter Checklist</h2>
            </div>
            <i data-lucide="list-filter" aria-hidden="true"></i>
        </header>

        <form data-mobile-checklists-filter>
            <div class="mobile-checklists-filter__grid">
                <label class="mobile-checklists-field">
                    <span>Kategori / Frekuensi</span>
                    <select name="category">
                        <option value="">Semua kategori</option>
                        @foreach ($mobileChecklistCategories as $category)
                            <option value="{{ $category['key'] }}">{{ $category['label'] }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="mobile-checklists-field">
                    <span>Status</span>
                    <select name="status">
                        <option value="">Semua status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                </label>
            </div>

            <label class="mobile-checklists-field mobile-checklists-field--search">
                <span>Cari item checklist</span>
                <span>
                    <i data-lucide="search" aria-hidden="true"></i>
                    <input name="query" type="search" placeholder="Nama item atau kriteria" autocomplete="off">
                </span>
            </label>

            <div class="mobile-checklists-filter__actions">
                <button type="submit">
                    <i data-lucide="search" aria-hidden="true"></i>
                    Terapkan
                </button>
                <button type="button" data-mobile-checklists-reset>
                    <i data-lucide="rotate-ccw" aria-hidden="true"></i>
                    Reset
                </button>
            </div>
        </form>
    </section>

    <section class="mobile-checklists-summary" aria-labelledby="mobile-checklists-summary-title">
        <header class="mobile-checklists-section-heading">
            <div>
                <p>Sesuai filter aktif</p>
                <h2 id="mobile-checklists-summary-title">Ringkasan Checklist</h2>
            </div>
        </header>

        <div class="mobile-checklists-summary__grid">
            <article class="mobile-checklists-summary-card mobile-checklists-summary-card--category">
                <span aria-hidden="true"><i data-lucide="folders"></i></span>
                <div><small>Total Kategori</small><strong data-mobile-checklists-summary="categories">6</strong></div>
            </article>
            <article class="mobile-checklists-summary-card mobile-checklists-summary-card--total">
                <span aria-hidden="true"><i data-lucide="list-checks"></i></span>
                <div><small>Total Item</small><strong data-mobile-checklists-summary="total">8</strong></div>
            </article>
            <article class="mobile-checklists-summary-card mobile-checklists-summary-card--active">
                <span aria-hidden="true"><i data-lucide="circle-check-big"></i></span>
                <div><small>Item Aktif</small><strong data-mobile-checklists-summary="active">7</strong></div>
            </article>
            <article class="mobile-checklists-summary-card mobile-checklists-summary-card--inactive">
                <span aria-hidden="true"><i data-lucide="circle-off"></i></span>
                <div><small>Item Nonaktif</small><strong data-mobile-checklists-summary="inactive">1</strong></div>
            </article>
        </div>
    </section>

    <section class="mobile-checklists-categories" aria-labelledby="mobile-checklists-list-title">
        <header class="mobile-checklists-section-heading mobile-checklists-section-heading--list">
            <div>
                <p>Master data</p>
                <h2 id="mobile-checklists-list-title">Kategori Pemeriksaan</h2>
            </div>
            <span data-mobile-checklists-result-count>8 item ditampilkan</span>
        </header>

        <div class="mobile-checklists-accordion" data-mobile-checklists-accordion>
            @foreach ($mobileChecklistCategories as $categoryIndex => $category)
                @php
                    $categoryItems = array_values(array_filter(
                        $mobileChecklistItems,
                        fn (array $item): bool => $item['category'] === $category['key'],
                    ));
                    $panelId = 'mobile-checklist-category-'.$category['key'];
                @endphp
                <section class="mobile-checklist-category" data-mobile-checklist-category="{{ $category['key'] }}">
                    <button
                        class="mobile-checklist-category__toggle"
                        type="button"
                        data-mobile-checklist-category-toggle
                        aria-expanded="{{ $categoryIndex === 0 ? 'true' : 'false' }}"
                        aria-controls="{{ $panelId }}"
                    >
                        <span class="mobile-checklist-category__icon" aria-hidden="true">
                            <i data-lucide="{{ $category['icon'] }}"></i>
                        </span>
                        <span>
                            <strong>{{ $category['label'] }}</strong>
                            <small><b data-mobile-checklist-category-count>{{ count($categoryItems) }}</b> item &bull; {{ $category['label'] }}</small>
                        </span>
                        <span class="mobile-checklists-badge mobile-checklists-badge--active">Aktif</span>
                        <i data-lucide="chevron-down" aria-hidden="true"></i>
                    </button>

                    <div
                        class="mobile-checklist-category__panel"
                        id="{{ $panelId }}"
                        data-mobile-checklist-category-panel
                        @if ($categoryIndex !== 0) hidden @endif
                    >
                        <div data-mobile-checklist-item-list>
                            @foreach ($categoryItems as $item)
                                <article
                                    class="mobile-checklist-item"
                                    data-mobile-checklist-item
                                    data-id="{{ $item['id'] }}"
                                    data-name="{{ $item['name'] }}"
                                    data-category="{{ $item['category'] }}"
                                    data-category-label="{{ $item['category_label'] }}"
                                    data-frequency="{{ $item['frequency'] }}"
                                    data-frequency-label="{{ $item['frequency_label'] }}"
                                    data-criteria="{{ $item['criteria'] }}"
                                    data-input-type="{{ $item['input_type'] }}"
                                    data-input-label="{{ $item['input_label'] }}"
                                    data-status="{{ $item['status'] }}"
                                    data-status-label="{{ $item['status_label'] }}"
                                    data-note="{{ $item['note'] }}"
                                >
                                    <header>
                                        <span aria-hidden="true"><i data-lucide="clipboard-check"></i></span>
                                        <div>
                                            <p data-mobile-checklist-item-frequency>{{ $item['frequency_label'] }}</p>
                                            <h3 data-mobile-checklist-item-name>{{ $item['name'] }}</h3>
                                        </div>
                                        <span class="mobile-checklists-badge mobile-checklists-badge--{{ $item['status'] }}" data-mobile-checklist-item-status>{{ $item['status_label'] }}</span>
                                    </header>

                                    <div class="mobile-checklist-item__criteria">
                                        <span>Kriteria pemeriksaan</span>
                                        <p data-mobile-checklist-item-criteria>{{ $item['criteria'] }}</p>
                                    </div>

                                    <dl>
                                        <div><dt>Tipe input</dt><dd data-mobile-checklist-item-input>{{ $item['input_label'] }}</dd></div>
                                        <div><dt>Frekuensi</dt><dd data-mobile-checklist-item-frequency-detail>{{ $item['frequency_label'] }}</dd></div>
                                    </dl>

                                    <footer>
                                        <button type="button" data-mobile-checklist-detail data-checklist-id="{{ $item['id'] }}">
                                            <i data-lucide="eye" aria-hidden="true"></i>
                                            Detail
                                        </button>
                                        <button type="button" data-mobile-checklist-edit data-checklist-id="{{ $item['id'] }}">
                                            <i data-lucide="pencil" aria-hidden="true"></i>
                                            Edit
                                        </button>
                                    </footer>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endforeach
        </div>

        <div class="mobile-checklists-empty" data-mobile-checklists-empty hidden>
            <span aria-hidden="true"><i data-lucide="clipboard-x"></i></span>
            <h3>Checklist tidak ditemukan</h3>
            <p>Ubah filter atau tekan Reset untuk menampilkan kembali item checklist.</p>
        </div>
    </section>

    <button class="mobile-checklists-fab" type="button" data-mobile-checklist-create>
        <i data-lucide="plus" aria-hidden="true"></i>
        <span>Tambah Checklist</span>
    </button>

    <div class="mobile-checklist-sheet" data-mobile-checklist-sheet="detail" aria-hidden="true" hidden>
        <button class="mobile-checklist-sheet__overlay" type="button" data-mobile-checklist-sheet-close tabindex="-1" aria-label="Tutup detail checklist"></button>
        <section
            class="mobile-checklist-sheet__panel"
            data-mobile-checklist-sheet-panel
            role="dialog"
            aria-modal="true"
            aria-labelledby="mobile-checklist-detail-title"
            tabindex="-1"
        >
            <div class="mobile-checklist-sheet__handle" aria-hidden="true"></div>
            <header>
                <div><p>Informasi pemeriksaan</p><h2 id="mobile-checklist-detail-title">Detail Checklist</h2></div>
                <button type="button" data-mobile-checklist-sheet-close aria-label="Tutup detail checklist"><i data-lucide="x" aria-hidden="true"></i></button>
            </header>

            <div class="mobile-checklist-detail__hero">
                <span aria-hidden="true"><i data-lucide="clipboard-list"></i></span>
                <div><small data-mobile-checklist-detail-field="categoryLabel">Kategori</small><h3 data-mobile-checklist-detail-field="name">Nama item</h3></div>
                <span class="mobile-checklists-badge" data-mobile-checklist-detail-field="statusLabel">Status</span>
            </div>

            <dl class="mobile-checklist-detail__grid">
                <div><dt>Kategori</dt><dd data-mobile-checklist-detail-field="categoryLabel">-</dd></div>
                <div><dt>Frekuensi</dt><dd data-mobile-checklist-detail-field="frequencyLabel">-</dd></div>
                <div class="mobile-checklist-detail__wide"><dt>Kriteria</dt><dd data-mobile-checklist-detail-field="criteria">-</dd></div>
                <div><dt>Tipe input</dt><dd data-mobile-checklist-detail-field="inputLabel">-</dd></div>
                <div><dt>Status</dt><dd data-mobile-checklist-detail-field="statusLabel">-</dd></div>
                <div class="mobile-checklist-detail__wide"><dt>Catatan</dt><dd data-mobile-checklist-detail-field="note">-</dd></div>
            </dl>

            <button class="mobile-checklist-sheet__secondary-button" type="button" data-mobile-checklist-sheet-close>Tutup Detail</button>
        </section>
    </div>

    <div class="mobile-checklist-sheet" data-mobile-checklist-sheet="form" aria-hidden="true" hidden>
        <button class="mobile-checklist-sheet__overlay" type="button" data-mobile-checklist-sheet-close tabindex="-1" aria-label="Tutup form checklist"></button>
        <section
            class="mobile-checklist-sheet__panel mobile-checklist-sheet__panel--form"
            data-mobile-checklist-sheet-panel
            role="dialog"
            aria-modal="true"
            aria-labelledby="mobile-checklist-form-title"
            tabindex="-1"
        >
            <div class="mobile-checklist-sheet__handle" aria-hidden="true"></div>
            <header>
                <div><p>Data master pemeriksaan</p><h2 id="mobile-checklist-form-title" data-mobile-checklist-form-title>Tambah Checklist</h2></div>
                <button type="button" data-mobile-checklist-sheet-close aria-label="Tutup form checklist"><i data-lucide="x" aria-hidden="true"></i></button>
            </header>

            <form class="mobile-checklist-form" data-mobile-checklist-form>
                <label class="mobile-checklists-field">
                    <span>Nama item pemeriksaan</span>
                    <input name="name" type="text" placeholder="Contoh: Pemeriksaan lead curtain" required>
                </label>

                <div class="mobile-checklist-form__grid">
                    <label class="mobile-checklists-field">
                        <span>Kategori</span>
                        <select name="category" required>
                            @foreach ($mobileChecklistCategories as $category)
                                <option value="{{ $category['key'] }}">{{ $category['label'] }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="mobile-checklists-field">
                        <span>Frekuensi</span>
                        <select name="frequency" required>
                            @foreach ($mobileChecklistCategories as $category)
                                <option value="{{ $category['key'] }}">{{ $category['label'] }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <label class="mobile-checklists-field">
                    <span>Kriteria pemeriksaan</span>
                    <textarea name="criteria" rows="3" placeholder="Tuliskan standar pemeriksaan" required></textarea>
                </label>

                <div class="mobile-checklist-form__grid">
                    <label class="mobile-checklists-field">
                        <span>Tipe input</span>
                        <select name="inputType" required>
                            <option value="boolean">Sesuai/Tidak Sesuai</option>
                            <option value="note">Catatan</option>
                            <option value="numeric">Nilai + Catatan</option>
                            <option value="boolean_note">Sesuai/Tidak Sesuai + Catatan</option>
                        </select>
                    </label>
                    <label class="mobile-checklists-field">
                        <span>Status</span>
                        <select name="status" required>
                            <option value="active">Aktif</option>
                            <option value="inactive">Nonaktif</option>
                        </select>
                    </label>
                </div>

                <label class="mobile-checklists-field">
                    <span>Catatan opsional</span>
                    <textarea name="note" rows="3" placeholder="Tambahkan petunjuk untuk petugas"></textarea>
                </label>

                <div class="mobile-checklist-form__actions">
                    <button type="button" data-mobile-checklist-sheet-close>Batal</button>
                    <button type="submit"><i data-lucide="save" aria-hidden="true"></i>Simpan</button>
                </div>
            </form>
        </section>
    </div>

    <div class="mobile-checklists-toast" data-mobile-checklists-toast role="status" aria-live="polite" hidden>
        <span aria-hidden="true"><i data-lucide="circle-check-big"></i></span>
        <p data-mobile-checklists-toast-message></p>
        <button type="button" data-mobile-checklists-toast-close aria-label="Tutup notifikasi"><i data-lucide="x" aria-hidden="true"></i></button>
    </div>
</main>
