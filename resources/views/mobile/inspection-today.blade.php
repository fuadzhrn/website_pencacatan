@php
    $isPetugas = auth()->user()->role === \App\UserRole::Petugas;
    $mobileStatusClasses = ['scheduled' => 'scheduled', 'pending' => 'pending', 'in_progress' => 'processing', 'overdue' => 'overdue'];
@endphp

<div class="mobile-inspection" data-mobile-inspection>
    <header class="mobile-inspection-header">
        <span class="mobile-inspection-header__icon" aria-hidden="true"><i data-lucide="clipboard-check"></i></span>
        <div><p>Pelaksanaan Maintenance</p><h1>Pemeriksaan Hari Ini</h1><span>Isi checklist berdasarkan jadwal aktif</span></div>
        <time datetime="{{ now()->toDateString() }}">{{ now()->translatedFormat('d M Y') }}</time>
    </header>

    @if (session('success'))
        <div class="mobile-inspection-notice mobile-inspection-notice--success" role="status"><i data-lucide="circle-check" aria-hidden="true"></i><span>{{ session('success') }}</span></div>
    @endif
    @if ($errors->any())
        <div class="mobile-inspection-notice mobile-inspection-notice--error" role="alert"><i data-lucide="triangle-alert" aria-hidden="true"></i><span>{{ $errors->first() }}</span></div>
    @endif

    <section class="mobile-inspection-section" aria-labelledby="mobile-machine-list-title">
        <div class="mobile-inspection-section__heading">
            <div><p>Agenda {{ $isPetugas ? 'Petugas' : 'Monitoring' }}</p><h2 id="mobile-machine-list-title">Jadwal Pemeriksaan</h2></div>
            <span>{{ $schedules->count() }} jadwal</span>
        </div>

        @if ($schedules->isEmpty())
            <div class="mobile-inspection-empty"><i data-lucide="calendar-x" aria-hidden="true"></i><strong>Tidak ada jadwal aktif hari ini.</strong></div>
        @else
            <div class="mobile-machine-list">
                @foreach ($schedules as $schedule)
                    @php
                        $isSelected = $selectedSchedule?->is($schedule) ?? false;
                        $statusClass = $mobileStatusClasses[$schedule->status] ?? 'scheduled';
                    @endphp
                    <article @class(['mobile-machine-card', 'is-selected' => $isSelected])>
                        <div class="mobile-machine-card__identity">
                            <span class="mobile-machine-card__icon" aria-hidden="true"><i data-lucide="scan-line"></i></span>
                            <div><h3>{{ $schedule->machine->equipment_name }}</h3><p><i data-lucide="map-pin" aria-hidden="true"></i>{{ $schedule->machine->location }}</p></div>
                        </div>
                        <span class="mobile-inspection-badge mobile-inspection-badge--{{ $statusClass }}">{{ $scheduleStatusLabels[$schedule->status] ?? ucfirst($schedule->status) }}</span>
                        <dl class="mobile-machine-card__meta">
                            <div><dt>Frekuensi</dt><dd>{{ $frequencyLabels[$schedule->maintenanceCategory->frequency] ?? ucfirst($schedule->maintenanceCategory->frequency) }}</dd></div>
                            <div><dt>Jam</dt><dd>{{ substr((string) $schedule->scheduled_time, 0, 5) }}</dd></div>
                            <div><dt>Petugas</dt><dd>{{ $schedule->assignedUser->name }}</dd></div>
                        </dl>
                        @if ($isPetugas && ! $schedule->check)
                            <form method="POST" action="{{ route('inspections.start', $schedule) }}">
                                @csrf
                                <input type="hidden" name="context" value="mobile">
                                <button type="submit">Mulai<i data-lucide="arrow-right" aria-hidden="true"></i></button>
                            </form>
                        @else
                            <a class="mobile-inspection-link" href="{{ route('mobile.inspection-today', ['schedule' => $schedule->id]) }}">
                                {{ $isPetugas && $schedule->status === 'in_progress' ? 'Lanjutkan' : 'Detail' }}<i data-lucide="arrow-right" aria-hidden="true"></i>
                            </a>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    @if ($selectedSchedule)
        @php
            $mobileCheck = $selectedSchedule->check;
            $mobileTotal = $checklistItems->count();
            $mobileCompleted = $checkDetails->whereNotIn('result_status', ['belum_diperiksa'])->count();
        @endphp

        <section class="mobile-selected-machine" aria-labelledby="mobile-selected-machine-title">
            <header>
                <span aria-hidden="true"><i data-lucide="scan-line"></i></span>
                <div><p>Mesin terpilih</p><h2 id="mobile-selected-machine-title">{{ $selectedSchedule->machine->equipment_name }}</h2></div>
                <span>{{ $frequencyLabels[$selectedSchedule->maintenanceCategory->frequency] ?? ucfirst($selectedSchedule->maintenanceCategory->frequency) }}</span>
            </header>
            <dl>
                <div><dt>Merk</dt><dd>{{ $selectedSchedule->machine->brand }}</dd></div>
                <div><dt>Tipe</dt><dd>{{ $selectedSchedule->machine->type }}</dd></div>
                <div><dt>Serial Number</dt><dd>{{ $selectedSchedule->machine->serial_number }}</dd></div>
                <div><dt>Lokasi</dt><dd>{{ $selectedSchedule->machine->location }}</dd></div>
                <div><dt>Kategori</dt><dd>{{ $selectedSchedule->maintenanceCategory->name }}</dd></div>
                <div><dt>Petugas</dt><dd>{{ $selectedSchedule->assignedUser->name }}</dd></div>
            </dl>
        </section>

        @if (! $canEditInspection)
            <div class="mobile-inspection-notice mobile-inspection-notice--info"><i data-lucide="eye" aria-hidden="true"></i><span>{{ $mobileCheck?->verification_status === 'verified' ? 'Pemeriksaan telah diverifikasi dan dikunci.' : 'Mode monitoring: data hanya dapat dilihat.' }}</span></div>
        @endif

        @if ($checklistItems->isEmpty())
            <div class="mobile-inspection-empty"><i data-lucide="list-x" aria-hidden="true"></i><strong>Belum ada item checklist aktif.</strong></div>
        @else
            <form class="mobile-inspection-form" method="POST" action="{{ route('inspections.store', $selectedSchedule) }}" enctype="multipart/form-data" data-inspection-form>
                @csrf
                <input type="hidden" name="maintenance_schedule_id" value="{{ $selectedSchedule->id }}">
                <input type="hidden" name="context" value="mobile">

                <section class="mobile-checklist-panel" aria-labelledby="mobile-checklist-title">
                    <header class="mobile-checklist-panel__header">
                        <div><p>Checklist digital</p><h2 id="mobile-checklist-title">{{ $selectedSchedule->maintenanceCategory->name }}</h2><span>{{ $mobileTotal }} item aktif</span></div>
                        @if ($canEditInspection)
                            <button type="button" data-mark-all-conform><i data-lucide="check-check" aria-hidden="true"></i>Semua Sesuai</button>
                        @endif
                    </header>

                    <div class="mobile-checklist-progress">
                        <div><span>Progress</span><strong data-progress-percentage>{{ $mobileTotal > 0 ? round(($mobileCompleted / $mobileTotal) * 100) : 0 }}%</strong></div>
                        <span class="mobile-checklist-progress__track"><span data-progress-bar style="width: {{ $mobileTotal > 0 ? ($mobileCompleted / $mobileTotal) * 100 : 0 }}%"></span></span>
                        <small data-progress-caption>{{ $mobileCompleted }} dari {{ $mobileTotal }} item diperiksa</small>
                    </div>

                    <div class="mobile-checklist-list">
                        @foreach ($checklistItems as $item)
                            @php
                                $detail = $checkDetails->get($item->id);
                                $finding = $detail?->finding;
                                $selectedResult = old("items.{$item->id}.result_status", $detail?->result_status ?? 'belum_diperiksa');
                                $canEditFinding = $canEditInspection && (! $finding || ($finding->status === 'open' && $finding->reviewed_by === null));
                            @endphp
                            <article @class(['mobile-checklist-item', 'is-conform' => $selectedResult === 'sesuai', 'is-nonconform' => $selectedResult === 'tidak_sesuai']) data-inspection-item>
                                <input type="hidden" name="items[{{ $item->id }}][maintenance_item_id]" value="{{ $item->id }}" @disabled(! $canEditInspection)>
                                <input type="hidden" name="items[{{ $item->id }}][result_status]" value="{{ $selectedResult }}" data-result-input @disabled(! $canEditInspection)>

                                <header><span aria-hidden="true"><i data-lucide="clipboard"></i></span><div><small>{{ $inputTypeLabels[$item->input_type] ?? $item->input_type }}</small><h3>{{ $item->item_name }}</h3><p>{{ $item->criteria }}</p></div></header>
                                <div class="mobile-checklist-item__results" role="group" aria-label="Hasil {{ $item->item_name }}">
                                    @foreach ($resultStatusLabels as $resultValue => $resultLabel)
                                        <button class="{{ $selectedResult === $resultValue ? 'is-active' : '' }}" type="button" data-inspection-result="{{ $resultValue }}" aria-pressed="{{ $selectedResult === $resultValue ? 'true' : 'false' }}" @disabled(! $canEditInspection)>{{ $resultLabel }}</button>
                                    @endforeach
                                </div>

                                @if ($item->input_type === 'nilai_aktual')
                                    <label><span>Nilai aktual</span><input type="text" name="items[{{ $item->id }}][actual_value]" value="{{ old("items.{$item->id}.actual_value", $detail?->actual_value) }}" @disabled(! $canEditInspection)></label>
                                @endif
                                <label><span>Catatan item</span><textarea name="items[{{ $item->id }}][note]" rows="2" @disabled(! $canEditInspection)>{{ old("items.{$item->id}.note", $detail?->note) }}</textarea></label>
                                @if ($canEditInspection)
                                    <label><span>Foto opsional</span><input type="file" name="items[{{ $item->id }}][photo]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"></label>
                                @elseif ($detail?->photo)
                                    <a href="{{ route('inspections.details.photo', [$mobileCheck, $detail]) }}" target="_blank" rel="noopener">Lihat foto pemeriksaan</a>
                                @endif
                                <section class="mobile-finding-fields" data-finding-fields @if ($selectedResult !== 'tidak_sesuai') hidden @endif>
                                    <strong>Data temuan</strong>
                                    @if ($finding && ! $canEditFinding)<p>Temuan sudah {{ $finding->status === 'resolved' ? 'diselesaikan' : 'ditinjau' }} dan dikunci.</p>@endif
                                    <label><span>Keterangan temuan</span><textarea name="items[{{ $item->id }}][finding_description]" rows="3" data-finding-description @disabled(! $canEditFinding)>{{ old("items.{$item->id}.finding_description", $finding?->finding_description) }}</textarea></label>
                                    <label><span>Tindak lanjut</span><textarea name="items[{{ $item->id }}][follow_up]" rows="2" @disabled(! $canEditFinding)>{{ old("items.{$item->id}.follow_up", $finding?->follow_up) }}</textarea></label>
                                    @if ($canEditFinding)<label><span>Foto temuan (opsional)</span><input type="file" name="items[{{ $item->id }}][finding_photo]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"></label>@elseif ($finding?->photo)<a href="{{ route('inspections.findings.photo', [$mobileCheck, $finding]) }}" target="_blank" rel="noopener">Lihat foto temuan</a>@endif
                                </section>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section class="mobile-inspection-notes">
                    <label for="mobile-general-note">Catatan pemeriksaan</label>
                    <textarea id="mobile-general-note" name="general_note" rows="4" @disabled(! $canEditInspection)>{{ old('general_note', $mobileCheck?->general_note) }}</textarea>
                </section>

                @if ($canEditInspection)
                    <footer class="mobile-inspection-actions">
                        <button type="submit" name="submission_type" value="draft"><i data-lucide="save" aria-hidden="true"></i>Simpan Draft</button>
                        <button type="submit" name="submission_type" value="final"><i data-lucide="clipboard-check" aria-hidden="true"></i>Simpan Pemeriksaan</button>
                    </footer>
                @endif
            </form>
        @endif
    @else
        <div class="mobile-inspection-empty mobile-inspection-empty--selection"><i data-lucide="mouse-pointer-click" aria-hidden="true"></i><strong>Pilih atau mulai salah satu jadwal.</strong></div>
    @endif
</div>
