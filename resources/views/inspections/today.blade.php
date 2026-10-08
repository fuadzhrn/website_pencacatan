@extends('layouts.app')

@section('title', 'Pemeriksaan Hari Ini')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/today-inspection.css') }}">
@endpush

@php
    $machines = [
        [
            'key' => 'baggage',
            'name' => 'X-Ray Baggage Smiths Detection HI-Scan 100100T',
            'brand' => 'Smiths Detection',
            'type' => 'HI-Scan 100100T',
            'serial' => 'XR-100100T-001',
            'location' => 'Terminal 1',
            'airport' => 'UPBU Malikussaleh',
            'frequency' => 'Harian',
            'officer' => 'Budi Santoso',
            'time' => '08:00 WITA',
            'status' => 'Belum Dikerjakan',
            'status_modifier' => 'pending',
            'action_label' => 'Mulai Pemeriksaan',
        ],
        [
            'key' => 'cabin',
            'name' => 'X-Ray Cabin',
            'brand' => 'Rapiscan Systems',
            'type' => '620XR HP',
            'serial' => 'XR-CABIN-002',
            'location' => 'Terminal 2',
            'airport' => 'UPBU Malikussaleh',
            'frequency' => 'Harian',
            'officer' => 'Andi Pratama',
            'time' => '09:30 WITA',
            'status' => 'Sedang Diproses',
            'status_modifier' => 'processing',
            'action_label' => 'Lanjutkan',
        ],
        [
            'key' => 'cargo',
            'name' => 'X-Ray Cargo',
            'brand' => 'Nuctech',
            'type' => 'CX100100TI',
            'serial' => 'XR-CARGO-003',
            'location' => 'Area Cargo',
            'airport' => 'UPBU Malikussaleh',
            'frequency' => 'Mingguan',
            'officer' => 'Siti Rahma',
            'time' => '13:00 WITA',
            'status' => 'Terjadwal',
            'status_modifier' => 'scheduled',
            'action_label' => 'Mulai Pemeriksaan',
        ],
    ];

    $checklistGroups = [
        'Safety Check' => [
            ['id' => 'lead-curtain', 'item' => 'Lead curtain dalam kondisi baik', 'criteria' => 'Tidak sobek, tidak renggang, dan terpasang sempurna.'],
        ],
        'Pembersihan' => [
            ['id' => 'outer-unit', 'item' => 'Unit bagian luar dalam kondisi bersih', 'criteria' => 'Tidak terdapat debu, cairan, atau benda asing pada permukaan unit.'],
        ],
        'Control Elements' => [
            ['id' => 'emergency-stop', 'item' => 'Emergency stop berfungsi', 'criteria' => 'Tombol menghentikan operasi dan dapat dikembalikan ke posisi normal.'],
        ],
        'Supply Voltage' => [
            ['id' => 'supply-voltage', 'item' => 'Tegangan input mesin stabil', 'criteria' => 'Nilai tegangan berada pada rentang operasional yang ditentukan.'],
        ],
        'Indicator Lamp' => [
            ['id' => 'indicator-lamp', 'item' => 'Indicator lamp menyala normal', 'criteria' => 'Seluruh lampu indikator menyala sesuai kondisi operasi.'],
        ],
        'Monitor' => [
            ['id' => 'monitor-display', 'item' => 'Monitor menampilkan gambar dengan jelas', 'criteria' => 'Citra tajam, stabil, dan tidak mengalami distorsi.'],
        ],
        'UPS' => [
            ['id' => 'ups-condition', 'item' => 'UPS dalam kondisi normal', 'criteria' => 'Tidak ada alarm, suara kipas normal, dan indikator daya aktif.'],
        ],
        'Functional Test' => [
            ['id' => 'conveyor-belt', 'item' => 'Conveyor belt berjalan normal', 'criteria' => 'Bergerak stabil, tidak sobek, dan tidak bergeser dari jalur.'],
            ['id' => 'zoom-function', 'item' => 'Tombol Zoom-in dan Zoom-out berfungsi', 'criteria' => 'Pembesaran serta pengecilan citra merespons dengan baik.'],
            ['id' => 'abnormal-sound', 'item' => 'Tidak ada suara abnormal saat mesin berjalan', 'criteria' => 'Mesin beroperasi tanpa bunyi gesekan atau getaran berlebih.'],
        ],
    ];

    $totalChecklist = array_sum(array_map('count', $checklistGroups));
@endphp

@section('content')
    <div class="today-inspection-page">
        <header class="today-inspection-header">
            <div class="today-inspection-header__content">
                <span class="today-inspection-header__icon" aria-hidden="true"><i data-lucide="clipboard-check"></i></span>
                <div>
                    <p class="today-inspection-header__eyebrow">Pelaksanaan Maintenance</p>
                    <h2>Pemeriksaan Hari Ini</h2>
                    <p>Isi checklist preventive maintenance berdasarkan jadwal hari ini.</p>
                </div>
            </div>
            <time class="today-inspection-date" datetime="2026-10-08">
                <span aria-hidden="true"><i data-lucide="calendar-days"></i></span>
                <span><small>Tanggal pemeriksaan</small><strong>Kamis, 08 Oktober 2026</strong></span>
            </time>
        </header>

        <section class="inspection-machines" aria-labelledby="inspection-machines-title">
            <header class="inspection-section-heading">
                <div>
                    <p class="inspection-section-heading__eyebrow">Agenda Petugas</p>
                    <h2 id="inspection-machines-title">Daftar Mesin Hari Ini</h2>
                    <p>Pilih mesin untuk membuka checklist pemeriksaan.</p>
                </div>
                <span class="inspection-section-heading__count">3 mesin</span>
            </header>

            <div class="inspection-machines__grid">
                @foreach ($machines as $index => $machine)
                    <article @class(['inspection-machine-card', 'is-selected' => $index === 0]) data-machine-card="{{ $machine['key'] }}">
                        <div class="inspection-machine-card__top">
                            <span class="inspection-machine-card__icon" aria-hidden="true"><i data-lucide="scan-line"></i></span>
                            <span class="inspection-status inspection-status--{{ $machine['status_modifier'] }}">{{ $machine['status'] }}</span>
                        </div>
                        <div class="inspection-machine-card__identity">
                            <h3>{{ $machine['name'] }}</h3>
                            <p><i data-lucide="map-pin" aria-hidden="true"></i>{{ $machine['location'] }}</p>
                        </div>
                        <dl class="inspection-machine-card__meta">
                            <div><dt>Frekuensi</dt><dd>{{ $machine['frequency'] }}</dd></div>
                            <div><dt>Jam</dt><dd>{{ $machine['time'] }}</dd></div>
                        </dl>
                        <button
                            @class(['inspection-machine-card__button', 'is-primary' => $index === 0])
                            type="button"
                            data-machine-select="{{ $machine['key'] }}"
                            data-name="{{ $machine['name'] }}"
                            data-brand="{{ $machine['brand'] }}"
                            data-type="{{ $machine['type'] }}"
                            data-serial="{{ $machine['serial'] }}"
                            data-location="{{ $machine['location'] }}"
                            data-airport="{{ $machine['airport'] }}"
                            data-frequency="{{ $machine['frequency'] }}"
                            data-officer="{{ $machine['officer'] }}"
                            data-status="{{ $machine['status'] }}"
                            data-action-label="{{ $machine['action_label'] }}"
                            aria-pressed="{{ $index === 0 ? 'true' : 'false' }}"
                        >
                            <i data-lucide="{{ $machine['status_modifier'] === 'processing' ? 'rotate-cw' : 'play' }}" aria-hidden="true"></i>
                            {{ $machine['action_label'] }}
                        </button>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="inspection-machine-detail" aria-labelledby="selected-machine-title">
            <header class="inspection-machine-detail__header">
                <span aria-hidden="true"><i data-lucide="scan-search"></i></span>
                <div>
                    <p>Mesin terpilih</p>
                    <h2 id="selected-machine-title" data-machine-detail="name">{{ $machines[0]['name'] }}</h2>
                </div>
                <span class="inspection-machine-detail__frequency" data-machine-detail="frequency">{{ $machines[0]['frequency'] }}</span>
            </header>
            <dl class="inspection-machine-detail__grid">
                <div><dt>Merk</dt><dd data-machine-detail="brand">{{ $machines[0]['brand'] }}</dd></div>
                <div><dt>Tipe</dt><dd data-machine-detail="type">{{ $machines[0]['type'] }}</dd></div>
                <div><dt>Serial Number</dt><dd data-machine-detail="serial">{{ $machines[0]['serial'] }}</dd></div>
                <div><dt>Lokasi</dt><dd data-machine-detail="location">{{ $machines[0]['location'] }}</dd></div>
                <div><dt>Bandar Udara</dt><dd data-machine-detail="airport">{{ $machines[0]['airport'] }}</dd></div>
                <div><dt>Petugas</dt><dd data-machine-detail="officer">{{ $machines[0]['officer'] }}</dd></div>
            </dl>
        </section>

        <form class="inspection-form" data-inspection-form novalidate>
            <div class="inspection-workspace">
                <div class="inspection-checklist-panel">
                    <header class="inspection-checklist-panel__header">
                        <div>
                            <p class="inspection-section-heading__eyebrow">Checklist Digital</p>
                            <h2>Checklist Pemeriksaan</h2>
                            <p>Pilih hasil pemeriksaan untuk setiap item di bawah ini.</p>
                        </div>
                        <button class="button button--secondary" type="button" data-mark-all-conform>
                            <i data-lucide="list-checks" aria-hidden="true"></i>
                            Tandai Semua Sesuai
                        </button>
                    </header>

                    <div class="inspection-checklist-groups">
                        @foreach ($checklistGroups as $group => $items)
                            <section class="inspection-checklist-group" aria-labelledby="group-{{ Str::slug($group) }}">
                                <header class="inspection-checklist-group__header">
                                    <span aria-hidden="true"><i data-lucide="folder-check"></i></span>
                                    <div>
                                        <h3 id="group-{{ Str::slug($group) }}">{{ $group }}</h3>
                                        <p>{{ count($items) }} item pemeriksaan</p>
                                    </div>
                                </header>

                                <div class="inspection-checklist-group__items">
                                    @foreach ($items as $item)
                                        <article class="inspection-checklist-item" data-checklist-item="{{ $item['id'] }}">
                                            <div class="inspection-checklist-item__content">
                                                <span class="inspection-checklist-item__marker" aria-hidden="true"><i data-lucide="clipboard"></i></span>
                                                <div>
                                                    <h4>{{ $item['item'] }}</h4>
                                                    <p><strong>Kriteria:</strong> {{ $item['criteria'] }}</p>
                                                </div>
                                            </div>
                                            <div class="inspection-result-options" role="group" aria-label="Hasil {{ $item['item'] }}">
                                                <button class="inspection-result-button inspection-result-button--conform" type="button" data-result-option="conform" aria-pressed="false">
                                                    <i data-lucide="check" aria-hidden="true"></i>Sesuai
                                                </button>
                                                <button class="inspection-result-button inspection-result-button--nonconform" type="button" data-result-option="nonconform" aria-pressed="false">
                                                    <i data-lucide="x" aria-hidden="true"></i>Tidak Sesuai
                                                </button>
                                            </div>
                                            <label class="inspection-item-note">
                                                <span>Catatan item <small data-note-hint>Opsional</small></span>
                                                <textarea rows="2" data-item-note placeholder="Tuliskan keterangan jika terdapat kendala…"></textarea>
                                            </label>
                                        </article>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>

                    <section class="inspection-general-note" aria-labelledby="inspection-general-note-title">
                        <div>
                            <span aria-hidden="true"><i data-lucide="notebook-pen"></i></span>
                            <div>
                                <h3 id="inspection-general-note-title">Catatan Pemeriksaan</h3>
                                <p>Tambahkan informasi umum yang perlu diketahui supervisor.</p>
                            </div>
                        </div>
                        <textarea rows="4" data-general-note placeholder="Tambahkan catatan umum pemeriksaan hari ini…"></textarea>
                    </section>
                </div>

                <aside class="inspection-summary" aria-labelledby="inspection-summary-title">
                    <header class="inspection-summary__header">
                        <span aria-hidden="true"><i data-lucide="chart-no-axes-column-increasing"></i></span>
                        <div>
                            <p>Progress saat ini</p>
                            <h2 id="inspection-summary-title">Ringkasan Pemeriksaan</h2>
                        </div>
                    </header>

                    <div class="inspection-summary__progress">
                        <div>
                            <span>Progress checklist</span>
                            <strong data-progress-percentage>0%</strong>
                        </div>
                        <div class="inspection-progress-track" role="progressbar" aria-label="Progress pemeriksaan" aria-valuemin="0" aria-valuemax="{{ $totalChecklist }}" aria-valuenow="0" data-progress-track>
                            <span data-progress-bar style="width: 0%"></span>
                        </div>
                        <small data-progress-caption>0 dari {{ $totalChecklist }} item telah diperiksa</small>
                    </div>

                    <dl class="inspection-summary__stats">
                        <div class="inspection-summary-stat inspection-summary-stat--total"><dt>Total checklist</dt><dd>{{ $totalChecklist }}</dd></div>
                        <div class="inspection-summary-stat inspection-summary-stat--conform"><dt>Sesuai</dt><dd data-conform-count>0</dd></div>
                        <div class="inspection-summary-stat inspection-summary-stat--nonconform"><dt>Tidak sesuai</dt><dd data-nonconform-count>0</dd></div>
                        <div class="inspection-summary-stat inspection-summary-stat--remaining"><dt>Belum diisi</dt><dd data-remaining-count>{{ $totalChecklist }}</dd></div>
                    </dl>

                    <div class="inspection-final-status">
                        <span>Status akhir</span>
                        <strong class="inspection-final-status__badge inspection-final-status__badge--incomplete" data-final-status>Belum Lengkap</strong>
                        <p data-final-status-note>Lengkapi seluruh item sebelum menyimpan pemeriksaan.</p>
                    </div>

                    <div class="inspection-summary__machine">
                        <span aria-hidden="true"><i data-lucide="scan-line"></i></span>
                        <div>
                            <small>Mesin aktif</small>
                            <strong data-summary-machine>{{ $machines[0]['name'] }}</strong>
                        </div>
                    </div>
                </aside>
            </div>

            <footer class="inspection-form-actions">
                <p><i data-lucide="info" aria-hidden="true"></i> Data masih berupa simulasi dan belum tersimpan ke database.</p>
                <div>
                    <button class="button button--neutral" type="button" data-cancel-inspection>
                        <i data-lucide="x" aria-hidden="true"></i>Batal
                    </button>
                    <button class="button button--secondary" type="button" data-save-draft>
                        <i data-lucide="save" aria-hidden="true"></i>Simpan Draft
                    </button>
                    <button class="button button--primary" type="submit">
                        <i data-lucide="clipboard-check" aria-hidden="true"></i>Simpan Pemeriksaan
                    </button>
                </div>
            </footer>
        </form>
    </div>

    <div class="inspection-toast" role="status" aria-live="polite" data-inspection-toast hidden>
        <span aria-hidden="true"><i data-lucide="circle-check"></i></span>
        <p data-toast-message>Notifikasi pemeriksaan.</p>
        <button type="button" data-toast-close aria-label="Tutup notifikasi"><i data-lucide="x" aria-hidden="true"></i></button>
    </div>

    <dialog class="inspection-success-dialog" id="inspection-success-dialog" aria-labelledby="inspection-success-title">
        <div class="inspection-success-dialog__content">
            <span class="inspection-success-dialog__icon" aria-hidden="true"><i data-lucide="badge-check"></i></span>
            <p>Simulasi Penyimpanan</p>
            <h2 id="inspection-success-title">Hasil pemeriksaan berhasil disimpan.</h2>
            <span data-success-description>Seluruh checklist telah tercatat sebagai pemeriksaan normal.</span>
            <button class="button button--primary" type="button" data-success-close>Tutup</button>
        </div>
    </dialog>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('assets/js/today-inspection.js') }}"></script>
@endpush
