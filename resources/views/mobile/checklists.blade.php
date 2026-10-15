@php
    $mobileChecklistQuery = request()->except(['category_detail', 'item_detail', 'context']);
    $mobileChecklistsUrl = route('mobile.checklists', $mobileChecklistQuery);
@endphp
<main class="mobile-checklists" data-mobile-checklists data-open-form="{{ old('form_type') }}" data-auto-detail="{{ $selectedItem ? 'true' : 'false' }}">
    <header class="mobile-checklists-header">
        <span class="mobile-checklists-header__icon" aria-hidden="true"><i data-lucide="list-checks"></i></span>
        <div><p>Konfigurasi pemeriksaan</p><h1>Master Checklist</h1><span>Kelola kategori dan item pemeriksaan</span></div>
    </header>

    @if (session('success'))
        <div class="mobile-checklists-alert mobile-checklists-alert--success" role="status"><i data-lucide="circle-check-big"></i><span>{{ session('success') }}</span></div>
    @endif
    @if (session('error'))
        <div class="mobile-checklists-alert mobile-checklists-alert--error" role="alert"><i data-lucide="triangle-alert"></i><span>{{ session('error') }}</span></div>
    @endif
    @if ($errors->any())
        <div class="mobile-checklists-alert mobile-checklists-alert--error" role="alert"><i data-lucide="circle-alert"></i><div><strong>Data belum dapat disimpan.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
    @endif

    <section class="mobile-checklists-filter" aria-labelledby="mobile-checklists-filter-title">
        <header class="mobile-checklists-section-heading"><div><p>Pencarian ringkas</p><h2 id="mobile-checklists-filter-title">Filter Checklist</h2></div><i data-lucide="list-filter"></i></header>
        <form method="GET" action="{{ route('mobile.checklists') }}" data-mobile-checklists-filter>
            <div class="mobile-checklists-filter__grid">
                <label class="mobile-checklists-field"><span>Kategori</span><select name="category"><option value="">Semua kategori</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected((string) $category->id === $filters['category'])>{{ $category->name }}</option>@endforeach</select></label>
                <label class="mobile-checklists-field"><span>Frekuensi</span><select name="frequency"><option value="">Semua frekuensi</option>@foreach ($frequencyOptions as $value => $label)<option value="{{ $value }}" @selected($value === $filters['frequency'])>{{ $label }}</option>@endforeach</select></label>
                <label class="mobile-checklists-field"><span>Tipe input</span><select name="input_type"><option value="">Semua tipe</option>@foreach ($inputTypeOptions as $value => $label)<option value="{{ $value }}" @selected($value === $filters['inputType'])>{{ $label }}</option>@endforeach</select></label>
                <label class="mobile-checklists-field"><span>Status</span><select name="status"><option value="">Semua status</option>@foreach ($statusOptions as $value => $label)<option value="{{ $value }}" @selected($value === $filters['status'])>{{ $label }}</option>@endforeach</select></label>
            </div>
            <label class="mobile-checklists-field mobile-checklists-field--search"><span>Cari item checklist</span><span><i data-lucide="search"></i><input name="search" type="search" value="{{ $filters['search'] }}" placeholder="Nama item atau kriteria"></span></label>
            <div class="mobile-checklists-filter__actions"><button type="submit"><i data-lucide="search"></i>Terapkan</button><a class="mobile-checklists-reset-link" href="{{ route('mobile.checklists') }}"><i data-lucide="rotate-ccw"></i>Reset</a></div>
        </form>
    </section>

    <section class="mobile-checklists-summary" aria-labelledby="mobile-checklists-summary-title">
        <header class="mobile-checklists-section-heading"><div><p>Data database</p><h2 id="mobile-checklists-summary-title">Ringkasan Checklist</h2></div></header>
        <div class="mobile-checklists-summary__grid">
            <article class="mobile-checklists-summary-card"><span><i data-lucide="folders"></i></span><div><small>Total Kategori</small><strong>{{ $summary['categories'] }}</strong></div></article>
            <article class="mobile-checklists-summary-card"><span><i data-lucide="list-checks"></i></span><div><small>Total Item</small><strong>{{ $summary['total'] }}</strong></div></article>
            <article class="mobile-checklists-summary-card mobile-checklists-summary-card--active"><span><i data-lucide="circle-check-big"></i></span><div><small>Item Aktif</small><strong>{{ $summary['active'] }}</strong></div></article>
            <article class="mobile-checklists-summary-card mobile-checklists-summary-card--inactive"><span><i data-lucide="circle-off"></i></span><div><small>Item Nonaktif</small><strong>{{ $summary['inactive'] }}</strong></div></article>
        </div>
    </section>

    <section class="mobile-checklists-categories" aria-labelledby="mobile-checklists-list-title">
        <header class="mobile-checklists-section-heading mobile-checklists-section-heading--list"><div><p>Master data</p><h2 id="mobile-checklists-list-title">Kategori Pemeriksaan</h2></div><span>{{ $items->count() }} item</span></header>
        <div class="mobile-checklists-category-actions"><button type="button" data-mobile-category-create><i data-lucide="folder-plus"></i>Tambah Kategori</button></div>

        <div class="mobile-checklists-accordion" data-mobile-checklists-accordion>
            @forelse ($categories as $categoryIndex => $category)
                @php
                    $categoryItems = $items->where('maintenance_category_id', $category->id);
                @endphp
                <section class="mobile-checklist-category" data-mobile-checklist-category="{{ $category->id }}">
                    <button class="mobile-checklist-category__toggle" type="button" data-mobile-checklist-category-toggle aria-expanded="{{ $categoryIndex === 0 ? 'true' : 'false' }}" aria-controls="mobile-checklist-category-{{ $category->id }}">
                        <span class="mobile-checklist-category__icon"><i data-lucide="folder-check"></i></span>
                        <span><strong>{{ $category->name }}</strong><small>{{ $frequencyOptions[$category->frequency] ?? $category->frequency }} &bull; <b>{{ $categoryItems->count() }}</b> item</small></span>
                        <span class="mobile-checklists-badge mobile-checklists-badge--{{ $category->status }}">{{ $statusOptions[$category->status] ?? $category->status }}</span>
                        <i data-lucide="chevron-down"></i>
                    </button>
                    <div class="mobile-checklist-category__panel" id="mobile-checklist-category-{{ $category->id }}" data-mobile-checklist-category-panel @if ($categoryIndex !== 0) hidden @endif>
                        <div class="mobile-checklist-category__actions">
                            <a href="{{ route('mobile.checklists', [...$mobileChecklistQuery, 'category_detail' => $category->id]) }}"><i data-lucide="eye"></i>Detail</a>
                            <button type="button" data-mobile-category-edit data-id="{{ $category->id }}" data-action="{{ route('maintenance-categories.update', $category) }}" data-name="{{ $category->name }}" data-frequency="{{ $category->frequency }}" data-description="{{ $category->description }}" data-status="{{ $category->status }}"><i data-lucide="pencil"></i>Edit</button>
                            <form method="POST" action="{{ route('maintenance-categories.toggle-status', $category) }}">@csrf @method('PATCH')<input type="hidden" name="context" value="mobile"><button type="submit"><i data-lucide="power"></i>Status</button></form>
                            <form method="POST" action="{{ route('maintenance-categories.destroy', $category) }}" data-confirm="Hapus kategori {{ $category->name }}?">@csrf @method('DELETE')<input type="hidden" name="context" value="mobile"><button type="submit"><i data-lucide="trash-2"></i>Hapus</button></form>
                        </div>
                        <div data-mobile-checklist-item-list>
                            @forelse ($categoryItems as $item)
                                <article class="mobile-checklist-item" data-mobile-checklist-item data-status="{{ $item->status }}">
                                    <header><span><i data-lucide="clipboard-check"></i></span><div><p>{{ $frequencyOptions[$category->frequency] ?? $category->frequency }}</p><h3>{{ $item->item_name }}</h3></div><span class="mobile-checklists-badge mobile-checklists-badge--{{ $item->status }}">{{ $statusOptions[$item->status] ?? $item->status }}</span></header>
                                    <div class="mobile-checklist-item__criteria"><span>Kriteria pemeriksaan</span><p>{{ $item->criteria }}</p></div>
                                    <dl><div><dt>Tipe input</dt><dd>{{ $inputTypeOptions[$item->input_type] ?? $item->input_type }}</dd></div><div><dt>Urutan</dt><dd>{{ $item->sort_order ?? '-' }}</dd></div></dl>
                                    <footer>
                                        <a href="{{ route('mobile.checklists', [...$mobileChecklistQuery, 'item_detail' => $item->id]) }}"><i data-lucide="eye"></i>Detail</a>
                                        <button type="button" data-mobile-checklist-edit data-id="{{ $item->id }}" data-action="{{ route('maintenance-items.update', $item) }}" data-category-id="{{ $item->maintenance_category_id }}" data-item-name="{{ $item->item_name }}" data-criteria="{{ $item->criteria }}" data-input-type="{{ $item->input_type }}" data-sort-order="{{ $item->sort_order }}" data-status="{{ $item->status }}"><i data-lucide="pencil"></i>Edit</button>
                                    </footer>
                                    <div class="mobile-checklist-item__secondary-actions">
                                        <form method="POST" action="{{ route('maintenance-items.toggle-status', $item) }}">@csrf @method('PATCH')<input type="hidden" name="context" value="mobile"><button type="submit"><i data-lucide="power"></i>Ubah Status</button></form>
                                        <form method="POST" action="{{ route('maintenance-items.destroy', $item) }}" data-confirm="Hapus item {{ $item->item_name }}?">@csrf @method('DELETE')<input type="hidden" name="context" value="mobile"><button type="submit"><i data-lucide="trash-2"></i>Hapus</button></form>
                                    </div>
                                </article>
                            @empty
                                <div class="mobile-checklists-empty"><span><i data-lucide="clipboard-x"></i></span><h3>Belum ada item</h3><p>Tambahkan item untuk kategori ini.</p></div>
                            @endforelse
                        </div>
                    </div>
                </section>
            @empty
                <div class="mobile-checklists-empty"><span><i data-lucide="folder-x"></i></span><h3>Belum ada kategori</h3><p>Tambahkan kategori pertama untuk menyusun checklist.</p></div>
            @endforelse
        </div>
        @if ($categories->isNotEmpty() && $items->isEmpty())
            <div class="mobile-checklists-empty"><span><i data-lucide="search-x"></i></span><h3>Checklist tidak ditemukan</h3><p>Ubah filter atau tambahkan item baru.</p></div>
        @endif
    </section>

    <button class="mobile-checklists-fab" type="button" data-mobile-checklist-create><i data-lucide="plus"></i><span>Tambah Checklist</span></button>

    @if ($selectedCategory)
        <div class="mobile-checklist-sheet" data-mobile-checklist-sheet="category-detail" aria-hidden="false">
            <a class="mobile-checklist-sheet__overlay" href="{{ $mobileChecklistsUrl }}" aria-label="Tutup detail"></a>
            <section class="mobile-checklist-sheet__panel is-static-open" role="dialog" aria-modal="true" tabindex="-1"><div class="mobile-checklist-sheet__handle"></div><header><div><p>Informasi kategori</p><h2>{{ $selectedCategory->name }}</h2></div><a href="{{ $mobileChecklistsUrl }}" aria-label="Tutup detail"><i data-lucide="x"></i></a></header><dl class="mobile-checklist-detail__grid"><div><dt>Frekuensi</dt><dd>{{ $frequencyOptions[$selectedCategory->frequency] ?? $selectedCategory->frequency }}</dd></div><div><dt>Status</dt><dd>{{ $statusOptions[$selectedCategory->status] ?? $selectedCategory->status }}</dd></div><div><dt>Jumlah item</dt><dd>{{ $selectedCategory->items_count }}</dd></div><div class="mobile-checklist-detail__wide"><dt>Deskripsi</dt><dd>{{ $selectedCategory->description ?: '-' }}</dd></div></dl></section>
        </div>
    @endif

    @if ($selectedItem)
        <div class="mobile-checklist-sheet is-open" data-mobile-checklist-sheet="detail" aria-hidden="false">
            <a class="mobile-checklist-sheet__overlay" href="{{ $mobileChecklistsUrl }}" aria-label="Tutup detail"></a>
            <section class="mobile-checklist-sheet__panel" role="dialog" aria-modal="true" tabindex="-1"><div class="mobile-checklist-sheet__handle"></div><header><div><p>Informasi pemeriksaan</p><h2>Detail Checklist</h2></div><a href="{{ $mobileChecklistsUrl }}" aria-label="Tutup detail"><i data-lucide="x"></i></a></header><div class="mobile-checklist-detail__hero"><span><i data-lucide="clipboard-list"></i></span><div><small>{{ $selectedItem->category->name }}</small><h3>{{ $selectedItem->item_name }}</h3></div><span class="mobile-checklists-badge mobile-checklists-badge--{{ $selectedItem->status }}">{{ $statusOptions[$selectedItem->status] ?? $selectedItem->status }}</span></div><dl class="mobile-checklist-detail__grid"><div><dt>Kategori</dt><dd>{{ $selectedItem->category->name }}</dd></div><div><dt>Frekuensi</dt><dd>{{ $frequencyOptions[$selectedItem->category->frequency] ?? $selectedItem->category->frequency }}</dd></div><div class="mobile-checklist-detail__wide"><dt>Kriteria</dt><dd>{{ $selectedItem->criteria }}</dd></div><div><dt>Tipe input</dt><dd>{{ $inputTypeOptions[$selectedItem->input_type] ?? $selectedItem->input_type }}</dd></div><div><dt>Urutan</dt><dd>{{ $selectedItem->sort_order ?? '-' }}</dd></div></dl></section>
        </div>
    @endif

    <div class="mobile-checklist-sheet" data-mobile-checklist-sheet="category-form" aria-hidden="true" hidden>
        <button class="mobile-checklist-sheet__overlay" type="button" data-mobile-checklist-sheet-close></button><section class="mobile-checklist-sheet__panel mobile-checklist-sheet__panel--form" data-mobile-checklist-sheet-panel role="dialog" aria-modal="true" tabindex="-1"><div class="mobile-checklist-sheet__handle"></div><header><div><p>Master kategori</p><h2 data-mobile-category-form-title>{{ $categoryForm['isEditing'] ? 'Edit Kategori' : 'Tambah Kategori' }}</h2></div><button type="button" data-mobile-checklist-sheet-close><i data-lucide="x"></i></button></header>
        <form class="mobile-checklist-form" method="POST" action="{{ $categoryForm['action'] }}" data-mobile-category-form data-store-action="{{ route('maintenance-categories.store') }}">@csrf <input data-method-field type="hidden" name="_method" value="{{ $categoryForm['method'] }}"><input type="hidden" name="record_id" value="{{ $categoryForm['recordId'] }}" data-record-id-field><input type="hidden" name="context" value="mobile"><input type="hidden" name="form_type" value="category">
            <label class="mobile-checklists-field"><span>Nama kategori</span><input name="name" value="{{ old('form_type') === 'category' ? old('name') : '' }}" required>@if (old('form_type') === 'category') @error('name')<span class="form-error">{{ $message }}</span>@enderror @endif</label>
            <label class="mobile-checklists-field"><span>Frekuensi</span><select name="frequency" required>@foreach ($frequencyOptions as $value => $label)<option value="{{ $value }}" @selected(old('form_type') === 'category' && old('frequency') === $value)>{{ $label }}</option>@endforeach</select>@if (old('form_type') === 'category') @error('frequency')<span class="form-error">{{ $message }}</span>@enderror @endif</label>
            <label class="mobile-checklists-field"><span>Deskripsi</span><textarea name="description" rows="3">{{ old('form_type') === 'category' ? old('description') : '' }}</textarea>@if (old('form_type') === 'category') @error('description')<span class="form-error">{{ $message }}</span>@enderror @endif</label>
            <label class="mobile-checklists-field"><span>Status</span><select name="status" required>@foreach ($statusOptions as $value => $label)<option value="{{ $value }}" @selected(old('form_type') === 'category' && old('status', 'active') === $value)>{{ $label }}</option>@endforeach</select>@if (old('form_type') === 'category') @error('status')<span class="form-error">{{ $message }}</span>@enderror @endif</label>
            <div class="mobile-checklist-form__actions"><button type="button" data-mobile-checklist-sheet-close>Batal</button><button type="submit"><i data-lucide="save"></i>Simpan</button></div>
        </form></section>
    </div>

    <div class="mobile-checklist-sheet" data-mobile-checklist-sheet="form" aria-hidden="true" hidden>
        <button class="mobile-checklist-sheet__overlay" type="button" data-mobile-checklist-sheet-close></button><section class="mobile-checklist-sheet__panel mobile-checklist-sheet__panel--form" data-mobile-checklist-sheet-panel role="dialog" aria-modal="true" tabindex="-1"><div class="mobile-checklist-sheet__handle"></div><header><div><p>Data master pemeriksaan</p><h2 data-mobile-checklist-form-title>{{ $itemForm['isEditing'] ? 'Edit Checklist' : 'Tambah Checklist' }}</h2></div><button type="button" data-mobile-checklist-sheet-close><i data-lucide="x"></i></button></header>
        <form class="mobile-checklist-form" method="POST" action="{{ $itemForm['action'] }}" data-mobile-checklist-form data-store-action="{{ route('maintenance-items.store') }}">@csrf <input data-method-field type="hidden" name="_method" value="{{ $itemForm['method'] }}"><input type="hidden" name="record_id" value="{{ $itemForm['recordId'] }}" data-record-id-field><input type="hidden" name="context" value="mobile"><input type="hidden" name="form_type" value="item">
            <label class="mobile-checklists-field"><span>Nama item pemeriksaan</span><input name="item_name" value="{{ old('form_type') === 'item' ? old('item_name') : '' }}" required>@if (old('form_type') === 'item') @error('item_name')<span class="form-error">{{ $message }}</span>@enderror @endif</label>
            <label class="mobile-checklists-field"><span>Kategori</span><select name="maintenance_category_id" required><option value="">Pilih kategori</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected(old('form_type') === 'item' && (string) old('maintenance_category_id') === (string) $category->id)>{{ $category->name }}</option>@endforeach</select>@if (old('form_type') === 'item') @error('maintenance_category_id')<span class="form-error">{{ $message }}</span>@enderror @endif</label>
            <label class="mobile-checklists-field"><span>Kriteria pemeriksaan</span><textarea name="criteria" rows="3" required>{{ old('form_type') === 'item' ? old('criteria') : '' }}</textarea>@if (old('form_type') === 'item') @error('criteria')<span class="form-error">{{ $message }}</span>@enderror @endif</label>
            <div class="mobile-checklist-form__grid"><label class="mobile-checklists-field"><span>Tipe input</span><select name="input_type" required>@foreach ($inputTypeOptions as $value => $label)<option value="{{ $value }}" @selected(old('form_type') === 'item' && old('input_type') === $value)>{{ $label }}</option>@endforeach</select>@if (old('form_type') === 'item') @error('input_type')<span class="form-error">{{ $message }}</span>@enderror @endif</label><label class="mobile-checklists-field"><span>Status</span><select name="status" required>@foreach ($statusOptions as $value => $label)<option value="{{ $value }}" @selected(old('form_type') === 'item' && old('status', 'active') === $value)>{{ $label }}</option>@endforeach</select>@if (old('form_type') === 'item') @error('status')<span class="form-error">{{ $message }}</span>@enderror @endif</label></div>
            <label class="mobile-checklists-field"><span>Urutan</span><input name="sort_order" type="number" min="0" max="65535" value="{{ old('form_type') === 'item' ? old('sort_order') : '' }}">@if (old('form_type') === 'item') @error('sort_order')<span class="form-error">{{ $message }}</span>@enderror @endif</label>
            <div class="mobile-checklist-form__actions"><button type="button" data-mobile-checklist-sheet-close>Batal</button><button type="submit"><i data-lucide="save"></i>Simpan</button></div>
        </form></section>
    </div>
</main>
