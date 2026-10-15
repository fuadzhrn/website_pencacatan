@extends('layouts.app')

@section('title', 'Pemeriksaan Hari Ini')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/today-inspection.css') }}">
@endpush

@section('content')
    @php
        $isPetugas = auth()->user()->role === \App\UserRole::Petugas;
        $statusClasses = ['scheduled' => 'scheduled', 'pending' => 'pending', 'in_progress' => 'processing', 'overdue' => 'overdue'];
    @endphp

    <div class="today-inspection-page">
        <header class="today-inspection-header">
            <div class="today-inspection-header__content">
                <span class="today-inspection-header__icon" aria-hidden="true"><i data-lucide="clipboard-check"></i></span>
                <div>
                    <p class="today-inspection-header__eyebrow">Pelaksanaan maintenance</p>
                    <h2>Pemeriksaan Hari Ini</h2>
                    <p>Pilih jadwal dan isi checklist sesuai kondisi aktual mesin.</p>
                </div>
            </div>
            <div class="today-inspection-date">
                <span aria-hidden="true"><i data-lucide="calendar-days"></i></span>
                <span><small>Tanggal pemeriksaan</small><strong>{{ now()->translatedFormat('d F Y') }}</strong></span>
            </div>
        </header>

        @if (session('success'))
            <div class="inspection-feedback inspection-feedback--success" role="status"><i data-lucide="circle-check" aria-hidden="true"></i><span>{{ session('success') }}</span></div>
        @endif
        @if ($errors->any())
            <div class="inspection-feedback inspection-feedback--error" role="alert"><i data-lucide="triangle-alert" aria-hidden="true"></i><div><strong>Data belum dapat disimpan.</strong><span>{{ $errors->first() }}</span></div></div>
        @endif

        <section class="inspection-machines" aria-labelledby="inspection-schedule-title">
            <div class="inspection-section-heading">
                <div>
                    <p class="inspection-section-heading__eyebrow">Agenda operasional</p>
                    <h2 id="inspection-schedule-title">Jadwal Pemeriksaan</h2>
                    <p>{{ $isPetugas ? 'Jadwal yang ditugaskan kepada Anda hari ini.' : 'Seluruh jadwal pemeriksaan hari ini untuk monitoring.' }}</p>
                </div>
                <span class="inspection-section-heading__count">{{ $schedules->count() }} jadwal</span>
            </div>

            @if ($schedules->isEmpty())
                <div class="inspection-empty-state"><i data-lucide="calendar-x" aria-hidden="true"></i><div><strong>Tidak ada jadwal aktif hari ini</strong><p>Jadwal akan tampil saat sudah dibuat dan ditugaskan.</p></div></div>
            @else
                <div class="inspection-machines__grid">
                    @foreach ($schedules as $schedule)
                        @php
                            $statusClass = $statusClasses[$schedule->status] ?? 'scheduled';
                            $isSelected = $selectedSchedule?->is($schedule) ?? false;
                        @endphp
                        <article @class(['inspection-machine-card', 'is-selected' => $isSelected])>
                            <div class="inspection-machine-card__top">
                                <span class="inspection-machine-card__icon" aria-hidden="true"><i data-lucide="scan-line"></i></span>
                                <span class="inspection-status inspection-status--{{ $statusClass }}">{{ $scheduleStatusLabels[$schedule->status] ?? ucfirst($schedule->status) }}</span>
                            </div>
                            <div class="inspection-machine-card__identity">
                                <h3>{{ $schedule->machine->equipment_name }}</h3>
                                <p><i data-lucide="map-pin" aria-hidden="true"></i>{{ $schedule->machine->location }}</p>
                            </div>
                            <dl class="inspection-machine-card__meta">
                                <div><dt>Frekuensi</dt><dd>{{ $frequencyLabels[$schedule->maintenanceCategory->frequency] ?? ucfirst($schedule->maintenanceCategory->frequency) }}</dd></div>
                                <div><dt>Jam</dt><dd>{{ substr((string) $schedule->scheduled_time, 0, 5) }}</dd></div>
                                <div><dt>Petugas</dt><dd>{{ $schedule->assignedUser->name }}</dd></div>
                            </dl>

                            @if ($isPetugas && ! $schedule->check)
                                <form method="POST" action="{{ route('inspections.start', $schedule) }}">
                                    @csrf
                                    <button class="inspection-machine-card__button is-primary" type="submit">Mulai Pemeriksaan<i data-lucide="arrow-right" aria-hidden="true"></i></button>
                                </form>
                            @else
                                <a class="inspection-machine-card__button {{ $isSelected ? 'is-primary' : '' }}" href="{{ route('inspections.schedules.show', $schedule) }}">
                                    {{ $isPetugas && $schedule->status === 'in_progress' ? 'Lanjutkan' : 'Lihat Detail' }}<i data-lucide="arrow-right" aria-hidden="true"></i>
                                </a>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        @if ($selectedSchedule)
            @php
                $check = $selectedSchedule->check;
                $completedCount = $checkDetails->whereNotIn('result_status', ['belum_diperiksa'])->count();
                $conformCount = $checkDetails->where('result_status', 'sesuai')->count();
                $nonconformCount = $checkDetails->where('result_status', 'tidak_sesuai')->count();
                $totalCount = $checklistItems->count();
            @endphp

            <section class="inspection-machine-detail" aria-labelledby="selected-machine-title">
                <header class="inspection-machine-detail__header">
                    <span aria-hidden="true"><i data-lucide="scan-line"></i></span>
                    <div><p>Mesin terpilih</p><h2 id="selected-machine-title">{{ $selectedSchedule->machine->equipment_name }}</h2></div>
                    <span class="inspection-machine-detail__frequency">{{ $frequencyLabels[$selectedSchedule->maintenanceCategory->frequency] ?? ucfirst($selectedSchedule->maintenanceCategory->frequency) }}</span>
                </header>
                <dl class="inspection-machine-detail__grid">
                    <div><dt>Merk</dt><dd>{{ $selectedSchedule->machine->brand }}</dd></div>
                    <div><dt>Tipe</dt><dd>{{ $selectedSchedule->machine->type }}</dd></div>
                    <div><dt>Serial Number</dt><dd>{{ $selectedSchedule->machine->serial_number }}</dd></div>
                    <div><dt>Lokasi</dt><dd>{{ $selectedSchedule->machine->location }}</dd></div>
                    <div><dt>Kategori</dt><dd>{{ $selectedSchedule->maintenanceCategory->name }}</dd></div>
                    <div><dt>Petugas</dt><dd>{{ $selectedSchedule->assignedUser->name }}</dd></div>
                </dl>
            </section>

            @if (! $canEditInspection)
                <div class="inspection-feedback inspection-feedback--info"><i data-lucide="eye" aria-hidden="true"></i><span>{{ $check?->verification_status === 'verified' ? 'Pemeriksaan telah diverifikasi dan dikunci.' : 'Anda melihat pemeriksaan ini dalam mode monitoring.' }}</span></div>
            @endif

            @if ($checklistItems->isEmpty())
                <div class="inspection-empty-state"><i data-lucide="list-x" aria-hidden="true"></i><div><strong>Belum ada item checklist aktif</strong><p>Admin perlu menambahkan item pada kategori {{ $selectedSchedule->maintenanceCategory->name }}.</p></div></div>
            @else
                <form class="inspection-form" method="POST" action="{{ route('inspections.store', $selectedSchedule) }}" enctype="multipart/form-data" data-inspection-form>
                    @csrf
                    <input type="hidden" name="maintenance_schedule_id" value="{{ $selectedSchedule->id }}">
                    <input type="hidden" name="context" value="desktop">

                    <div class="inspection-workspace">
                        <section class="inspection-checklist-panel" aria-labelledby="checklist-title">
                            <header class="inspection-checklist-panel__header">
                                <div><p class="inspection-section-heading__eyebrow">Checklist digital</p><h2 id="checklist-title">{{ $selectedSchedule->maintenanceCategory->name }}</h2><p>{{ $totalCount }} item pemeriksaan aktif.</p></div>
                                @if ($canEditInspection)
                                    <button class="button button--secondary" type="button" data-mark-all-conform><i data-lucide="check-check" aria-hidden="true"></i>Tandai Semua Sesuai</button>
                                @endif
                            </header>

                            <div class="inspection-checklist-groups">
                                <section class="inspection-checklist-group">
                                    <div class="inspection-checklist-group__items">
                                        @foreach ($checklistItems as $item)
                                            @php
                                                $detail = $checkDetails->get($item->id);
                                                $finding = $detail?->finding;
                                                $selectedResult = old("items.{$item->id}.result_status", $detail?->result_status ?? 'belum_diperiksa');
                                                $canEditFinding = $canEditInspection && (! $finding || ($finding->status === 'open' && $finding->reviewed_by === null));
                                            @endphp
                                            <article @class(['inspection-checklist-item', 'is-conform' => $selectedResult === 'sesuai', 'is-nonconform' => $selectedResult === 'tidak_sesuai']) data-inspection-item>
                                                <input type="hidden" name="items[{{ $item->id }}][maintenance_item_id]" value="{{ $item->id }}" @disabled(! $canEditInspection)>
                                                <input type="hidden" name="items[{{ $item->id }}][result_status]" value="{{ $selectedResult }}" data-result-input @disabled(! $canEditInspection)>

                                                <div class="inspection-checklist-item__content">
                                                    <span class="inspection-checklist-item__marker" aria-hidden="true"><i data-lucide="clipboard"></i></span>
                                                    <div><h4>{{ $item->item_name }}</h4><p><strong>Kriteria:</strong> {{ $item->criteria }}</p><small>{{ $inputTypeLabels[$item->input_type] ?? $item->input_type }}</small></div>
                                                </div>

                                                <div class="inspection-result-options" role="group" aria-label="Hasil {{ $item->item_name }}">
                                                    @foreach ($resultStatusLabels as $resultValue => $resultLabel)
                                                        <button class="inspection-result-button inspection-result-button--{{ $resultValue }} {{ $selectedResult === $resultValue ? 'is-active' : '' }}" type="button" data-inspection-result="{{ $resultValue }}" aria-pressed="{{ $selectedResult === $resultValue ? 'true' : 'false' }}" @disabled(! $canEditInspection)>{{ $resultLabel }}</button>
                                                    @endforeach
                                                </div>

                                                @if ($item->input_type === 'nilai_aktual')
                                                    <label class="inspection-item-note"><span>Nilai aktual</span><input type="text" name="items[{{ $item->id }}][actual_value]" value="{{ old("items.{$item->id}.actual_value", $detail?->actual_value) }}" @disabled(! $canEditInspection)></label>
                                                @endif
                                                <label class="inspection-item-note"><span>Catatan item</span><textarea name="items[{{ $item->id }}][note]" rows="2" @disabled(! $canEditInspection)>{{ old("items.{$item->id}.note", $detail?->note) }}</textarea></label>
                                                @if ($canEditInspection)
                                                    <label class="inspection-item-note"><span>Foto opsional</span><input type="file" name="items[{{ $item->id }}][photo]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"></label>
                                                @elseif ($detail?->photo)
                                                    <a class="inspection-photo-link" href="{{ route('inspections.details.photo', [$check, $detail]) }}" target="_blank" rel="noopener">Lihat foto pemeriksaan</a>
                                                @endif
                                                <section class="inspection-finding-fields" data-finding-fields @if ($selectedResult !== 'tidak_sesuai') hidden @endif>
                                                    <strong>Data temuan</strong>
                                                    @if ($finding && ! $canEditFinding)
                                                        <p>Temuan sudah {{ $finding->status === 'resolved' ? 'diselesaikan' : 'ditinjau' }} dan tidak dapat diubah oleh petugas.</p>
                                                    @endif
                                                    <label class="inspection-item-note"><span>Keterangan temuan</span><textarea name="items[{{ $item->id }}][finding_description]" rows="3" data-finding-description @disabled(! $canEditFinding)>{{ old("items.{$item->id}.finding_description", $finding?->finding_description) }}</textarea></label>
                                                    <label class="inspection-item-note"><span>Tindak lanjut</span><textarea name="items[{{ $item->id }}][follow_up]" rows="2" @disabled(! $canEditFinding)>{{ old("items.{$item->id}.follow_up", $finding?->follow_up) }}</textarea></label>
                                                    @if ($canEditFinding)
                                                        <label class="inspection-item-note"><span>Foto temuan (opsional, maks. 2 MB)</span><input type="file" name="items[{{ $item->id }}][finding_photo]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"></label>
                                                    @elseif ($finding?->photo)
                                                        <a class="inspection-photo-link" href="{{ route('inspections.findings.photo', [$check, $finding]) }}" target="_blank" rel="noopener">Lihat foto temuan</a>
                                                    @endif
                                                </section>
                                            </article>
                                        @endforeach
                                    </div>
                                </section>
                            </div>

                            <section class="inspection-general-note" aria-labelledby="general-note-title">
                                <div><span aria-hidden="true"><i data-lucide="notebook-pen"></i></span><div><h3 id="general-note-title">Catatan Pemeriksaan</h3><p>Informasi umum untuk supervisor.</p></div></div>
                                <textarea name="general_note" rows="4" @disabled(! $canEditInspection)>{{ old('general_note', $check?->general_note) }}</textarea>
                            </section>
                        </section>

                        <aside class="inspection-summary" aria-labelledby="summary-title">
                            <header class="inspection-summary__header"><span aria-hidden="true"><i data-lucide="chart-no-axes-column-increasing"></i></span><div><p>Progress saat ini</p><h2 id="summary-title">Ringkasan Pemeriksaan</h2></div></header>
                            <div class="inspection-summary__progress">
                                <div><span>Progress checklist</span><strong data-progress-percentage>{{ $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0 }}%</strong></div>
                                <div class="inspection-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $totalCount }}" aria-valuenow="{{ $completedCount }}" data-progress-track><span data-progress-bar style="width: {{ $totalCount > 0 ? ($completedCount / $totalCount) * 100 : 0 }}%"></span></div>
                                <small data-progress-caption>{{ $completedCount }} dari {{ $totalCount }} item telah diperiksa</small>
                            </div>
                            <dl class="inspection-summary__stats">
                                <div class="inspection-summary-stat"><dt>Total checklist</dt><dd>{{ $totalCount }}</dd></div>
                                <div class="inspection-summary-stat inspection-summary-stat--conform"><dt>Sesuai</dt><dd data-conform-count>{{ $conformCount }}</dd></div>
                                <div class="inspection-summary-stat inspection-summary-stat--nonconform"><dt>Tidak sesuai</dt><dd data-nonconform-count>{{ $nonconformCount }}</dd></div>
                                <div class="inspection-summary-stat"><dt>Belum diperiksa</dt><dd data-remaining-count>{{ max(0, $totalCount - $completedCount) }}</dd></div>
                            </dl>
                        </aside>
                    </div>

                    @if ($canEditInspection)
                        <footer class="inspection-form-actions">
                            <p><i data-lucide="shield-check" aria-hidden="true"></i>Data tersimpan ke riwayat pemeriksaan setelah dikirim.</p>
                            <div>
                                <button class="button button--secondary" type="submit" name="submission_type" value="draft"><i data-lucide="save" aria-hidden="true"></i>Simpan Draft</button>
                                <button class="button button--primary" type="submit" name="submission_type" value="final"><i data-lucide="clipboard-check" aria-hidden="true"></i>Simpan Pemeriksaan</button>
                            </div>
                        </footer>
                    @endif
                </form>
            @endif
        @else
            <div class="inspection-empty-state inspection-empty-state--selection"><i data-lucide="mouse-pointer-click" aria-hidden="true"></i><div><strong>Pilih jadwal pemeriksaan</strong><p>Detail mesin dan checklist akan tampil setelah jadwal dipilih atau dimulai.</p></div></div>
        @endif
    </div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('assets/js/today-inspection.js') }}"></script>
@endpush
