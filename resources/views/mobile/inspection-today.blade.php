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

                <section class="mobile-inspection-progress" aria-labelledby="mobile-progress-title">
                    <header>
                        <div><p>Ringkasan Checklist</p><h2 id="mobile-progress-title">Progress Pemeriksaan</h2></div>
                        <span class="mobile-inspection-badge mobile-inspection-badge--{{ $mobileCompleted === $mobileTotal ? 'complete' : 'pending' }}" data-mobile-progress-status>{{ $mobileCompleted === $mobileTotal ? 'Lengkap' : 'Belum Lengkap' }}</span>
                    </header>
                    <div class="mobile-inspection-progress__value">
                        <strong data-progress-percentage>{{ $mobileTotal > 0 ? round(($mobileCompleted / $mobileTotal) * 100) : 0 }}%</strong>
                        <span data-progress-caption>{{ $mobileCompleted }} dari {{ $mobileTotal }} item telah diperiksa</span>
                    </div>
                    <div class="mobile-inspection-progress__track" role="progressbar" aria-label="Progress checklist" aria-valuemin="0" aria-valuemax="{{ $mobileTotal }}" aria-valuenow="{{ $mobileCompleted }}" data-progress-track>
                        <span data-progress-bar style="width: {{ $mobileTotal > 0 ? ($mobileCompleted / $mobileTotal) * 100 : 0 }}%"></span>
                    </div>
                    <dl>
                        <div><dt>Total</dt><dd>{{ $mobileTotal }}</dd></div>
                        <div><dt>Sesuai</dt><dd data-conform-count>{{ $checkDetails->where('result_status', 'sesuai')->count() }}</dd></div>
                        <div><dt>Tidak Sesuai</dt><dd data-nonconform-count>{{ $checkDetails->where('result_status', 'tidak_sesuai')->count() }}</dd></div>
                    </dl>
                </section>

                @if ($canEditInspection)
                    <button class="mobile-mark-all-button" type="button" data-mark-all-conform>
                        <i data-lucide="badge-check" aria-hidden="true"></i>
                        <span><strong>Tandai Semua Sesuai</strong><small>Isi seluruh checklist sebagai kondisi normal</small></span>
                    </button>
                @endif

                <section class="mobile-inspection-section" aria-labelledby="mobile-checklist-title">
                    <div class="mobile-inspection-section__heading">
                        <div><p>Form Pemeriksaan</p><h2 id="mobile-checklist-title">Checklist Maintenance</h2></div>
                        <span>{{ $mobileTotal }} item</span>
                    </div>

                    <div class="mobile-checklist-categories">
                        <section class="mobile-checklist-category" data-mobile-category>
                            <button class="mobile-checklist-category__toggle" type="button" aria-expanded="true" aria-controls="mobile-category-panel-{{ $selectedSchedule->id }}" data-mobile-category-toggle>
                                <span class="mobile-checklist-category__icon" aria-hidden="true"><i data-lucide="clipboard-list"></i></span>
                                <span><strong>{{ $selectedSchedule->maintenanceCategory->name }}</strong><small>{{ $mobileTotal }} item pemeriksaan &middot; {{ $frequencyLabels[$selectedSchedule->maintenanceCategory->frequency] ?? $selectedSchedule->maintenanceCategory->frequency }}</small></span>
                                <span class="mobile-category-badge" data-mobile-category-badge>Belum Lengkap</span>
                                <i data-lucide="chevron-down" aria-hidden="true"></i>
                            </button>

                            <div class="mobile-checklist-category__panel" id="mobile-category-panel-{{ $selectedSchedule->id }}" data-mobile-category-panel>
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

                                        <header>
                                            <span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                            <div><h3>{{ $item->item_name }}</h3><p>{{ $item->criteria }}</p></div>
                                        </header>

                                        <div class="mobile-checklist-item__choices" role="group" aria-label="Hasil {{ $item->item_name }}">
                                            <button class="{{ $selectedResult === 'sesuai' ? 'is-active' : '' }}" type="button" data-inspection-result="sesuai" aria-pressed="{{ $selectedResult === 'sesuai' ? 'true' : 'false' }}" @disabled(! $canEditInspection)><i data-lucide="check" aria-hidden="true"></i>{{ $resultStatusLabels['sesuai'] }}</button>
                                            <button class="{{ $selectedResult === 'tidak_sesuai' ? 'is-active' : '' }}" type="button" data-inspection-result="tidak_sesuai" aria-pressed="{{ $selectedResult === 'tidak_sesuai' ? 'true' : 'false' }}" @disabled(! $canEditInspection)><i data-lucide="x" aria-hidden="true"></i>{{ $resultStatusLabels['tidak_sesuai'] }}</button>
                                        </div>
                                        @error("items.{$item->id}.result_status")<span class="form-error">{{ $message }}</span>@enderror

                                        @if ($item->input_type === 'nilai_aktual')
                                            <label class="mobile-field"><span>Nilai aktual</span><input type="text" name="items[{{ $item->id }}][actual_value]" value="{{ old("items.{$item->id}.actual_value", $detail?->actual_value) }}" maxlength="255" @disabled(! $canEditInspection)>@error("items.{$item->id}.actual_value")<span class="form-error">{{ $message }}</span>@enderror</label>
                                        @endif
                                        <label class="mobile-field"><span>Catatan item <small>Opsional</small></span><textarea name="items[{{ $item->id }}][note]" rows="2" placeholder="Tambahkan catatan untuk item ini…" @disabled(! $canEditInspection)>{{ old("items.{$item->id}.note", $detail?->note) }}</textarea>@error("items.{$item->id}.note")<span class="form-error">{{ $message }}</span>@enderror</label>
                                        @if ($canEditInspection)
                                            <label class="mobile-photo-field">
                                                <span><i data-lucide="camera" aria-hidden="true"></i><strong>Foto pemeriksaan</strong><small>Opsional · JPG, PNG, WEBP · maks. 2 MB</small></span>
                                                <input type="file" name="items[{{ $item->id }}][photo]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-mobile-photo-input>
                                            </label>
                                            <div class="mobile-photo-preview" data-mobile-photo-preview hidden><img src="" alt="Preview foto pemeriksaan"><span data-mobile-photo-name></span></div>
                                            @error("items.{$item->id}.photo")<span class="form-error">{{ $message }}</span>@enderror
                                        @elseif ($detail?->photo)
                                            <a href="{{ route('inspections.details.photo', [$mobileCheck, $detail]) }}" target="_blank" rel="noopener">Lihat foto pemeriksaan</a>
                                        @endif

                                        <section class="mobile-finding-fields" data-finding-fields @if ($selectedResult !== 'tidak_sesuai') hidden @endif>
                                            <strong>Detail Temuan</strong>
                                            @if ($finding && ! $canEditFinding)<p>Temuan sudah {{ $finding->status === 'resolved' ? 'diselesaikan' : 'ditinjau' }} dan dikunci.</p>@endif
                                            <label class="mobile-field"><span>Keterangan temuan <small>Wajib</small></span><textarea name="items[{{ $item->id }}][finding_description]" rows="3" placeholder="Jelaskan kondisi temuan…" data-finding-description @disabled(! $canEditFinding)>{{ old("items.{$item->id}.finding_description", $finding?->finding_description) }}</textarea>@error("items.{$item->id}.finding_description")<span class="form-error">{{ $message }}</span>@enderror</label>
                                            <label class="mobile-field"><span>Tindak lanjut</span><textarea name="items[{{ $item->id }}][follow_up]" rows="2" placeholder="Tuliskan tindak lanjut yang diperlukan…" @disabled(! $canEditFinding)>{{ old("items.{$item->id}.follow_up", $finding?->follow_up) }}</textarea>@error("items.{$item->id}.follow_up")<span class="form-error">{{ $message }}</span>@enderror</label>
                                            @if ($canEditFinding)
                                                <label class="mobile-photo-field">
                                                    <span><i data-lucide="camera" aria-hidden="true"></i><strong>Foto temuan</strong><small>Opsional · JPG, PNG, WEBP · maks. 2 MB</small></span>
                                                    <input type="file" name="items[{{ $item->id }}][finding_photo]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-mobile-photo-input>
                                                </label>
                                                <div class="mobile-photo-preview" data-mobile-photo-preview hidden><img src="" alt="Preview foto temuan"><span data-mobile-photo-name></span></div>
                                                @error("items.{$item->id}.finding_photo")<span class="form-error">{{ $message }}</span>@enderror
                                            @elseif ($finding?->photo)
                                                <a href="{{ route('inspections.findings.photo', [$mobileCheck, $finding]) }}" target="_blank" rel="noopener">Lihat foto temuan</a>
                                            @endif
                                        </section>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    </div>
                </section>

                <section class="mobile-general-note" aria-labelledby="mobile-general-note-title">
                    <div><span aria-hidden="true"><i data-lucide="notebook-pen"></i></span><div><h2 id="mobile-general-note-title">Catatan Pemeriksaan</h2><p>Catatan umum untuk supervisor</p></div></div>
                    <textarea id="mobile-general-note" name="general_note" rows="4" placeholder="Tambahkan catatan umum pemeriksaan hari ini…" @disabled(! $canEditInspection)>{{ old('general_note', $mobileCheck?->general_note) }}</textarea>
                    @error('general_note')<span class="form-error">{{ $message }}</span>@enderror
                </section>

                @if ($canEditInspection)
                    <div class="mobile-inspection-actions" aria-label="Aksi pemeriksaan">
                        <button type="submit" name="submission_type" value="draft"><i data-lucide="save" aria-hidden="true"></i>Simpan Draft</button>
                        <button type="submit" name="submission_type" value="final"><i data-lucide="clipboard-check" aria-hidden="true"></i>Simpan Pemeriksaan</button>
                    </div>
                @endif
            </form>
        @endif
    @else
        <div class="mobile-inspection-empty mobile-inspection-empty--selection"><i data-lucide="mouse-pointer-click" aria-hidden="true"></i><strong>Pilih atau mulai salah satu jadwal.</strong></div>
    @endif
</div>
