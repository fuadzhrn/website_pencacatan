@php
    $mobileMachines = [
        [
            'key' => 'baggage',
            'name' => 'X-Ray Baggage HI-Scan 100100T',
            'brand' => 'Smiths Detection',
            'type' => 'HI-Scan 100100T',
            'serial' => 'XR-100100T-001',
            'location' => 'Terminal 1',
            'frequency' => 'Harian',
            'officer' => $userName,
            'time' => '08:00',
            'status' => 'Belum Dikerjakan',
            'status_modifier' => 'pending',
            'action' => 'Mulai',
        ],
        [
            'key' => 'cabin',
            'name' => 'X-Ray Cabin',
            'brand' => 'Rapiscan Systems',
            'type' => '620XR HP',
            'serial' => 'XR-CABIN-002',
            'location' => 'Terminal 2',
            'frequency' => 'Harian',
            'officer' => $userName,
            'time' => '09:30',
            'status' => 'Sedang Diproses',
            'status_modifier' => 'processing',
            'action' => 'Lanjutkan',
        ],
        [
            'key' => 'cargo',
            'name' => 'X-Ray Cargo',
            'brand' => 'Nuctech',
            'type' => 'CX100100TI',
            'serial' => 'XR-CARGO-003',
            'location' => 'Area Cargo',
            'frequency' => 'Mingguan',
            'officer' => $userName,
            'time' => '13:00',
            'status' => 'Terjadwal',
            'status_modifier' => 'scheduled',
            'action' => 'Mulai',
        ],
    ];

    $mobileChecklistGroups = [
        [
            'key' => 'safety-check',
            'name' => 'Safety Check',
            'icon' => 'shield-check',
            'items' => [
                ['id' => 'lead-curtain', 'name' => 'Lead curtain dalam kondisi baik', 'criteria' => 'Tidak robek, tidak aus, dan menutup dengan baik.'],
            ],
        ],
        [
            'key' => 'cleaning',
            'name' => 'Pembersihan',
            'icon' => 'sparkles',
            'items' => [
                ['id' => 'outer-unit', 'name' => 'Unit bagian luar dalam kondisi bersih', 'criteria' => 'Tidak terdapat debu, cairan, atau benda asing pada permukaan unit.'],
            ],
        ],
        [
            'key' => 'control-elements',
            'name' => 'Control Elements',
            'icon' => 'circle-stop',
            'items' => [
                ['id' => 'emergency-stop', 'name' => 'Emergency stop berfungsi', 'criteria' => 'Mesin berhenti saat tombol ditekan.'],
            ],
        ],
        [
            'key' => 'indicator-lamp',
            'name' => 'Indicator Lamp',
            'icon' => 'lightbulb',
            'items' => [
                ['id' => 'indicator-lamp', 'name' => 'Lampu indikator bekerja normal', 'criteria' => 'Seluruh lampu menyala sesuai kondisi operasi mesin.'],
            ],
        ],
        [
            'key' => 'monitor',
            'name' => 'Monitor',
            'icon' => 'monitor-check',
            'items' => [
                ['id' => 'monitor-display', 'name' => 'Monitor menampilkan gambar jelas', 'criteria' => 'Tampilan tidak buram dan tidak berkedip.'],
            ],
        ],
        [
            'key' => 'ups',
            'name' => 'UPS',
            'icon' => 'battery-charging',
            'items' => [
                ['id' => 'ups-condition', 'name' => 'UPS dalam kondisi normal', 'criteria' => 'Backup daya bekerja dengan baik.'],
            ],
        ],
        [
            'key' => 'functional-test',
            'name' => 'Functional Test',
            'icon' => 'settings-2',
            'items' => [
                ['id' => 'conveyor-belt', 'name' => 'Conveyor belt berjalan normal', 'criteria' => 'Tidak tersendat, tidak miring, dan suara normal.'],
            ],
        ],
    ];

    $mobileChecklistCount = array_sum(array_map(
        fn (array $group): int => count($group['items']),
        $mobileChecklistGroups,
    ));
@endphp

<div class="mobile-inspection" data-mobile-inspection data-initial-machine="{{ $mobileMachines[0]['key'] }}">
    <header class="mobile-inspection-header">
        <span class="mobile-inspection-header__icon" aria-hidden="true"><i data-lucide="clipboard-check"></i></span>
        <div>
            <p>Pelaksanaan Maintenance</p>
            <h1>Pemeriksaan Hari Ini</h1>
            <span>Isi checklist preventive maintenance</span>
        </div>
        <time datetime="2026-10-09">Jumat, 09 Oktober 2026</time>
    </header>

    <section class="mobile-inspection-section" aria-labelledby="mobile-machine-list-title">
        <div class="mobile-inspection-section__heading">
            <div><p>Agenda Petugas</p><h2 id="mobile-machine-list-title">Pilih Mesin Pemeriksaan</h2></div>
            <span>{{ count($mobileMachines) }} mesin</span>
        </div>

        <div class="mobile-machine-list">
            @foreach ($mobileMachines as $index => $machine)
                <article @class(['mobile-machine-card', 'is-selected' => $index === 0]) data-mobile-machine-card="{{ $machine['key'] }}">
                    <div class="mobile-machine-card__identity">
                        <span class="mobile-machine-card__icon" aria-hidden="true"><i data-lucide="scan-line"></i></span>
                        <div>
                            <h3>{{ $machine['name'] }}</h3>
                            <p><i data-lucide="map-pin" aria-hidden="true"></i>{{ $machine['location'] }}</p>
                        </div>
                    </div>
                    <span class="mobile-inspection-badge mobile-inspection-badge--{{ $machine['status_modifier'] }}">{{ $machine['status'] }}</span>
                    <dl class="mobile-machine-card__meta">
                        <div><dt>Frekuensi</dt><dd>{{ $machine['frequency'] }}</dd></div>
                        <div><dt>Jam</dt><dd>{{ $machine['time'] }}</dd></div>
                    </dl>
                    <button
                        type="button"
                        data-mobile-machine-select="{{ $machine['key'] }}"
                        data-name="{{ $machine['name'] }}"
                        data-brand="{{ $machine['brand'] }}"
                        data-type="{{ $machine['type'] }}"
                        data-serial="{{ $machine['serial'] }}"
                        data-location="{{ $machine['location'] }}"
                        data-frequency="{{ $machine['frequency'] }}"
                        data-officer="{{ $machine['officer'] }}"
                        aria-pressed="{{ $index === 0 ? 'true' : 'false' }}"
                    >
                        {{ $machine['action'] }}<i data-lucide="arrow-right" aria-hidden="true"></i>
                    </button>
                </article>
            @endforeach
        </div>
    </section>

    <section class="mobile-selected-machine" aria-labelledby="mobile-selected-machine-title">
        <header>
            <span aria-hidden="true"><i data-lucide="scan-line"></i></span>
            <div><p>Mesin terpilih</p><h2 id="mobile-selected-machine-title" data-mobile-machine-detail="name">{{ $mobileMachines[0]['name'] }}</h2></div>
            <span data-mobile-machine-detail="frequency">{{ $mobileMachines[0]['frequency'] }}</span>
        </header>
        <dl>
            <div><dt>Merk</dt><dd data-mobile-machine-detail="brand">{{ $mobileMachines[0]['brand'] }}</dd></div>
            <div><dt>Tipe</dt><dd data-mobile-machine-detail="type">{{ $mobileMachines[0]['type'] }}</dd></div>
            <div><dt>Serial Number</dt><dd data-mobile-machine-detail="serial">{{ $mobileMachines[0]['serial'] }}</dd></div>
            <div><dt>Lokasi</dt><dd data-mobile-machine-detail="location">{{ $mobileMachines[0]['location'] }}</dd></div>
            <div><dt>Frekuensi</dt><dd data-mobile-machine-detail="frequency">{{ $mobileMachines[0]['frequency'] }}</dd></div>
            <div><dt>Petugas</dt><dd data-mobile-machine-detail="officer">{{ $mobileMachines[0]['officer'] }}</dd></div>
        </dl>
    </section>

    <form class="mobile-inspection-form" data-mobile-inspection-form novalidate>
        <section class="mobile-inspection-progress" aria-labelledby="mobile-progress-title">
            <header>
                <div><p>Ringkasan Checklist</p><h2 id="mobile-progress-title">Progress Pemeriksaan</h2></div>
                <span class="mobile-inspection-badge mobile-inspection-badge--pending" data-mobile-progress-status>Belum Lengkap</span>
            </header>
            <div class="mobile-inspection-progress__value">
                <strong data-mobile-progress-percentage>0%</strong>
                <span data-mobile-progress-caption>0 dari {{ $mobileChecklistCount }} item terisi</span>
            </div>
            <div class="mobile-inspection-progress__track" role="progressbar" aria-label="Progress checklist" aria-valuemin="0" aria-valuemax="{{ $mobileChecklistCount }}" aria-valuenow="0" data-mobile-progress-track>
                <span data-mobile-progress-bar></span>
            </div>
            <dl>
                <div><dt>Total</dt><dd>{{ $mobileChecklistCount }}</dd></div>
                <div><dt>Sesuai</dt><dd data-mobile-conform-count>0</dd></div>
                <div><dt>Tidak Sesuai</dt><dd data-mobile-nonconform-count>0</dd></div>
            </dl>
        </section>

        <button class="mobile-mark-all-button" type="button" data-mobile-mark-all>
            <i data-lucide="badge-check" aria-hidden="true"></i>
            <span><strong>Tandai Semua Sesuai</strong><small>Isi seluruh checklist sebagai kondisi normal</small></span>
        </button>

        <section class="mobile-inspection-section" aria-labelledby="mobile-checklist-title">
            <div class="mobile-inspection-section__heading">
                <div><p>Form Pemeriksaan</p><h2 id="mobile-checklist-title">Checklist Maintenance</h2></div>
                <span>{{ count($mobileChecklistGroups) }} kategori</span>
            </div>

            <div class="mobile-checklist-categories">
                @foreach ($mobileChecklistGroups as $groupIndex => $group)
                    <section class="mobile-checklist-category" data-mobile-category="{{ $group['key'] }}">
                        <button
                            class="mobile-checklist-category__toggle"
                            type="button"
                            aria-expanded="{{ $groupIndex === 0 ? 'true' : 'false' }}"
                            aria-controls="mobile-category-panel-{{ $group['key'] }}"
                            data-mobile-category-toggle
                        >
                            <span class="mobile-checklist-category__icon" aria-hidden="true"><i data-lucide="{{ $group['icon'] }}"></i></span>
                            <span><strong>{{ $group['name'] }}</strong><small>{{ count($group['items']) }} item pemeriksaan</small></span>
                            <span class="mobile-category-badge" data-mobile-category-badge>Belum Lengkap</span>
                            <i data-lucide="chevron-down" aria-hidden="true"></i>
                        </button>

                        <div class="mobile-checklist-category__panel" id="mobile-category-panel-{{ $group['key'] }}" data-mobile-category-panel @if ($groupIndex !== 0) hidden @endif>
                            @foreach ($group['items'] as $item)
                                <article class="mobile-checklist-item" data-mobile-checklist-item="{{ $item['id'] }}">
                                    <header>
                                        <span>{{ str_pad((string) ($loop->parent->index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                        <div><h3>{{ $item['name'] }}</h3><p>{{ $item['criteria'] }}</p></div>
                                    </header>

                                    <div class="mobile-checklist-item__choices" role="group" aria-label="Hasil {{ $item['name'] }}">
                                        <button type="button" data-mobile-result="conform" aria-pressed="false"><i data-lucide="check" aria-hidden="true"></i>Sesuai</button>
                                        <button type="button" data-mobile-result="nonconform" aria-pressed="false"><i data-lucide="x" aria-hidden="true"></i>Tidak Sesuai</button>
                                    </div>

                                    <label class="mobile-field">
                                        <span>Catatan item <small>Opsional</small></span>
                                        <textarea rows="2" data-mobile-item-note placeholder="Tambahkan catatan untuk item ini…"></textarea>
                                    </label>

                                    <div class="mobile-finding-form" data-mobile-finding-form hidden>
                                        <div class="mobile-finding-form__heading">
                                            <span aria-hidden="true"><i data-lucide="triangle-alert"></i></span>
                                            <div><strong>Detail Temuan</strong><small>Lengkapi keterangan sebelum menyimpan</small></div>
                                        </div>
                                        <label class="mobile-field">
                                            <span>Keterangan temuan <small>Wajib</small></span>
                                            <textarea rows="3" data-mobile-finding-description placeholder="Jelaskan kondisi temuan…"></textarea>
                                        </label>
                                        <label class="mobile-field">
                                            <span>Tindak lanjut</span>
                                            <textarea rows="3" data-mobile-finding-follow-up placeholder="Tuliskan tindak lanjut yang diperlukan…"></textarea>
                                        </label>
                                        <label class="mobile-photo-field">
                                            <span><i data-lucide="camera" aria-hidden="true"></i><strong>Foto temuan</strong><small>Opsional · JPG atau PNG</small></span>
                                            <input type="file" accept="image/jpeg,image/png,image/webp" data-mobile-finding-photo>
                                        </label>
                                        <div class="mobile-photo-preview" data-mobile-photo-preview hidden>
                                            <img src="" alt="Preview foto temuan">
                                            <span data-mobile-photo-name></span>
                                        </div>
                                        <button class="mobile-apply-finding" type="button" data-mobile-apply-finding><i data-lucide="check-circle-2" aria-hidden="true"></i>Terapkan Temuan</button>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </section>

        <section class="mobile-general-note" aria-labelledby="mobile-general-note-title">
            <div><span aria-hidden="true"><i data-lucide="notebook-pen"></i></span><div><h2 id="mobile-general-note-title">Catatan Pemeriksaan</h2><p>Catatan umum untuk seluruh pemeriksaan</p></div></div>
            <textarea rows="4" data-mobile-general-note placeholder="Tambahkan catatan umum pemeriksaan hari ini…"></textarea>
        </section>

        <div class="mobile-inspection-actions" aria-label="Aksi pemeriksaan">
            <button type="button" data-mobile-save-draft><i data-lucide="save" aria-hidden="true"></i>Simpan Draft</button>
            <button type="submit"><i data-lucide="clipboard-check" aria-hidden="true"></i>Simpan Pemeriksaan</button>
        </div>
    </form>

    <div class="mobile-inspection-toast" role="status" aria-live="polite" data-mobile-inspection-toast hidden>
        <i data-lucide="circle-check" aria-hidden="true"></i>
        <span data-mobile-inspection-toast-message></span>
        <button type="button" data-mobile-inspection-toast-close aria-label="Tutup notifikasi"><i data-lucide="x" aria-hidden="true"></i></button>
    </div>
</div>
