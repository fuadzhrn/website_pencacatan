@extends('layouts.app')

@section('title', 'Master Checklist')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/checklists.css') }}">
@endpush

@section('content')
    <div
        class="checklists-page"
        data-checklists
        data-open-form="{{ old('form_type') }}"
    >
        <header class="checklists-page-header">
            <div class="checklists-page-header__content">
                <p class="checklists-page-header__eyebrow">Konfigurasi Pemeriksaan</p>
                <h2 class="checklists-page-header__title">Master Checklist</h2>
                <p class="checklists-page-header__description">Kelola kategori dan item pemeriksaan preventive maintenance mesin X-Ray.</p>
            </div>
            <div class="checklists-header-actions">
                <button class="checklists-secondary-button" type="button" data-category-create>
                    <i data-lucide="folder-plus" aria-hidden="true"></i> Tambah Kategori
                </button>
                <button class="checklists-primary-button" type="button" data-checklist-create>
                    <i data-lucide="plus" aria-hidden="true"></i> Tambah Item
                </button>
            </div>
        </header>

        @if (session('success'))
            <div class="checklists-alert checklists-alert--success" role="status">
                <i data-lucide="circle-check-big" aria-hidden="true"></i><span>{{ session('success') }}</span>
            </div>
        @endif
        @if (session('error'))
            <div class="checklists-alert checklists-alert--error" role="alert">
                <i data-lucide="triangle-alert" aria-hidden="true"></i><span>{{ session('error') }}</span>
            </div>
        @endif
        @if ($errors->any())
            <div class="checklists-alert checklists-alert--error" role="alert">
                <i data-lucide="circle-alert" aria-hidden="true"></i>
                <div><strong>Data belum dapat disimpan.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            </div>
        @endif

        @if ($selectedCategory)
            <section class="checklists-detail-panel" aria-labelledby="selected-category-title">
                <div><p>Detail kategori</p><h3 id="selected-category-title">{{ $selectedCategory->name }}</h3></div>
                <dl>
                    <div><dt>Frekuensi</dt><dd>{{ $frequencyOptions[$selectedCategory->frequency] ?? $selectedCategory->frequency }}</dd></div>
                    <div><dt>Status</dt><dd>{{ $statusOptions[$selectedCategory->status] ?? $selectedCategory->status }}</dd></div>
                    <div><dt>Jumlah item</dt><dd>{{ $selectedCategory->items_count }}</dd></div>
                    <div><dt>Deskripsi</dt><dd>{{ $selectedCategory->description ?: '-' }}</dd></div>
                </dl>
                <a href="{{ route('checklists.index') }}">Tutup detail</a>
            </section>
        @endif

        @if ($selectedItem)
            <section class="checklists-detail-panel" aria-labelledby="selected-item-title">
                <div><p>Detail item checklist</p><h3 id="selected-item-title">{{ $selectedItem->item_name }}</h3></div>
                <dl>
                    <div><dt>Kategori</dt><dd>{{ $selectedItem->category->name }}</dd></div>
                    <div><dt>Frekuensi</dt><dd>{{ $frequencyOptions[$selectedItem->category->frequency] ?? $selectedItem->category->frequency }}</dd></div>
                    <div><dt>Kriteria</dt><dd>{{ $selectedItem->criteria }}</dd></div>
                    <div><dt>Tipe input</dt><dd>{{ $inputTypeOptions[$selectedItem->input_type] ?? $selectedItem->input_type }}</dd></div>
                    <div><dt>Urutan</dt><dd>{{ $selectedItem->sort_order ?? '-' }}</dd></div>
                    <div><dt>Status</dt><dd>{{ $statusOptions[$selectedItem->status] ?? $selectedItem->status }}</dd></div>
                </dl>
                <a href="{{ route('checklists.index') }}">Tutup detail</a>
            </section>
        @endif

        <section class="checklists-summary-grid" aria-label="Ringkasan master checklist">
            <article class="checklists-summary-card checklists-summary-card--emerald"><div class="checklists-summary-card__head"><p class="checklists-summary-card__label">Total Kategori</p><span class="checklists-summary-card__icon"><i data-lucide="folders"></i></span></div><p class="checklists-summary-card__value">{{ $summary['categories'] }}</p><p class="checklists-summary-card__note">Kategori pemeriksaan</p></article>
            <article class="checklists-summary-card checklists-summary-card--green"><div class="checklists-summary-card__head"><p class="checklists-summary-card__label">Total Item</p><span class="checklists-summary-card__icon"><i data-lucide="list-checks"></i></span></div><p class="checklists-summary-card__value">{{ $summary['total'] }}</p><p class="checklists-summary-card__note">Seluruh item pemeriksaan</p></article>
            <article class="checklists-summary-card checklists-summary-card--amber"><div class="checklists-summary-card__head"><p class="checklists-summary-card__label">Item Aktif</p><span class="checklists-summary-card__icon"><i data-lucide="circle-check-big"></i></span></div><p class="checklists-summary-card__value">{{ $summary['active'] }}</p><p class="checklists-summary-card__note">Digunakan dalam pemeriksaan</p></article>
            <article class="checklists-summary-card checklists-summary-card--gray"><div class="checklists-summary-card__head"><p class="checklists-summary-card__label">Item Nonaktif</p><span class="checklists-summary-card__icon"><i data-lucide="circle-off"></i></span></div><p class="checklists-summary-card__value">{{ $summary['inactive'] }}</p><p class="checklists-summary-card__note">Tidak digunakan sementara</p></article>
        </section>

        <section class="checklists-filter-panel" aria-labelledby="checklists-filter-title">
            <div class="checklists-filter-panel__header"><div><h2 class="checklists-section-title" id="checklists-filter-title">Filter Checklist</h2><p class="checklists-section-note">Cari dan saring data dari database.</p></div><span class="checklists-count">{{ $items->count() }} item</span></div>
            <form class="checklists-filter-form" method="GET" action="{{ route('checklists.index') }}">
                <label><span>Pencarian</span><input name="search" type="search" value="{{ $filters['search'] }}" placeholder="Nama item atau kriteria"></label>
                <label><span>Kategori</span><select name="category"><option value="">Semua kategori</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected((string) $category->id === $filters['category'])>{{ $category->name }}</option>@endforeach</select></label>
                <label><span>Frekuensi</span><select name="frequency"><option value="">Semua frekuensi</option>@foreach ($frequencyOptions as $value => $label)<option value="{{ $value }}" @selected($value === $filters['frequency'])>{{ $label }}</option>@endforeach</select></label>
                <label><span>Tipe Input</span><select name="input_type"><option value="">Semua tipe</option>@foreach ($inputTypeOptions as $value => $label)<option value="{{ $value }}" @selected($value === $filters['inputType'])>{{ $label }}</option>@endforeach</select></label>
                <label><span>Status</span><select name="status"><option value="">Semua status</option>@foreach ($statusOptions as $value => $label)<option value="{{ $value }}" @selected($value === $filters['status'])>{{ $label }}</option>@endforeach</select></label>
                <div class="checklists-filter-actions"><button class="checklists-primary-button" type="submit"><i data-lucide="search"></i>Terapkan</button><a class="checklists-secondary-button" href="{{ route('checklists.index') }}"><i data-lucide="rotate-ccw"></i>Reset</a></div>
            </form>
        </section>

        <section class="checklists-table-panel" aria-labelledby="categories-table-title">
            <header class="checklists-table-panel__header"><div><h2 class="checklists-section-title" id="categories-table-title">Kategori Pemeriksaan</h2><p class="checklists-section-note">Kategori menentukan periode pemeriksaan.</p></div><span class="checklists-count">{{ $categories->count() }} kategori</span></header>
            <div class="checklists-table-wrap"><table class="checklists-table checklists-table--categories"><thead><tr><th>Nama</th><th>Frekuensi</th><th>Item</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
                @forelse ($categories as $category)
                    <tr><td><strong>{{ $category->name }}</strong><small class="checklists-block-note">{{ $category->description ?: 'Tanpa deskripsi' }}</small></td><td>{{ $frequencyOptions[$category->frequency] ?? $category->frequency }}</td><td>{{ $category->items_count }}</td><td><span class="checklist-status checklist-status--{{ $category->status }}">{{ $statusOptions[$category->status] ?? $category->status }}</span></td><td><div class="checklists-actions">
                        <a class="checklists-action-button checklists-action-button--detail" href="{{ route('maintenance-categories.show', $category) }}" title="Detail kategori"><i data-lucide="eye"></i></a>
                        <button class="checklists-action-button checklists-action-button--edit" type="button" title="Edit kategori" data-category-edit data-id="{{ $category->id }}" data-action="{{ route('maintenance-categories.update', $category) }}" data-name="{{ $category->name }}" data-frequency="{{ $category->frequency }}" data-description="{{ $category->description }}" data-status="{{ $category->status }}"><i data-lucide="pencil"></i></button>
                        <form method="POST" action="{{ route('maintenance-categories.toggle-status', $category) }}">@csrf @method('PATCH')<button class="checklists-action-button" type="submit" title="Ubah status"><i data-lucide="power"></i></button></form>
                        <form method="POST" action="{{ route('maintenance-categories.destroy', $category) }}" data-confirm="Hapus kategori {{ $category->name }}?">@csrf @method('DELETE')<button class="checklists-action-button checklists-action-button--delete" type="submit" title="Hapus kategori"><i data-lucide="trash-2"></i></button></form>
                    </div></td></tr>
                @empty
                    <tr class="checklists-empty-row"><td colspan="5"><div class="checklists-empty-state"><i data-lucide="folder-x"></i><strong>Belum ada kategori</strong><span>Tambahkan kategori pertama untuk mulai menyusun checklist.</span></div></td></tr>
                @endforelse
            </tbody></table></div>
        </section>

        <section class="checklists-table-panel" aria-labelledby="items-table-title">
            <header class="checklists-table-panel__header"><div><h2 class="checklists-section-title" id="items-table-title">Daftar Item Pemeriksaan</h2><p class="checklists-section-note">Item checklist yang sesuai dengan filter aktif.</p></div><span class="checklists-count">{{ $items->count() }} item</span></header>
            <div class="checklists-table-wrap"><table class="checklists-table"><thead><tr><th>No</th><th>Kategori</th><th>Item Pemeriksaan</th><th>Kriteria / Standar</th><th>Frekuensi</th><th>Tipe Input</th><th>Urutan</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
                @forelse ($items as $index => $item)
                    <tr><td class="checklists-table__number">{{ $index + 1 }}</td><td><span class="checklist-category">{{ $item->category->name }}</span></td><td class="checklists-item-name">{{ $item->item_name }}</td><td class="checklists-criteria">{{ $item->criteria }}</td><td><span class="checklist-frequency">{{ $frequencyOptions[$item->category->frequency] ?? $item->category->frequency }}</span></td><td><span class="checklist-input-type checklist-input-type--{{ $item->input_type === 'nilai_aktual' ? 'numeric' : ($item->input_type === 'catatan' ? 'note' : 'checklist') }}">{{ $inputTypeOptions[$item->input_type] ?? $item->input_type }}</span></td><td>{{ $item->sort_order ?? '-' }}</td><td><span class="checklist-status checklist-status--{{ $item->status }}">{{ $statusOptions[$item->status] ?? $item->status }}</span></td><td><div class="checklists-actions">
                        <a class="checklists-action-button checklists-action-button--detail" href="{{ route('maintenance-items.show', $item) }}" title="Detail item"><i data-lucide="eye"></i></a>
                        <button class="checklists-action-button checklists-action-button--edit" type="button" title="Edit item" data-checklist-edit data-id="{{ $item->id }}" data-action="{{ route('maintenance-items.update', $item) }}" data-category-id="{{ $item->maintenance_category_id }}" data-item-name="{{ $item->item_name }}" data-criteria="{{ $item->criteria }}" data-input-type="{{ $item->input_type }}" data-sort-order="{{ $item->sort_order }}" data-status="{{ $item->status }}"><i data-lucide="pencil"></i></button>
                        <form method="POST" action="{{ route('maintenance-items.toggle-status', $item) }}">@csrf @method('PATCH')<button class="checklists-action-button" type="submit" title="Ubah status"><i data-lucide="power"></i></button></form>
                        <form method="POST" action="{{ route('maintenance-items.destroy', $item) }}" data-confirm="Hapus item {{ $item->item_name }}?">@csrf @method('DELETE')<button class="checklists-action-button checklists-action-button--delete" type="submit" title="Hapus item"><i data-lucide="trash-2"></i></button></form>
                    </div></td></tr>
                @empty
                    <tr class="checklists-empty-row"><td colspan="9"><div class="checklists-empty-state"><i data-lucide="clipboard-x"></i><strong>Checklist tidak ditemukan</strong><span>Ubah filter atau tambahkan item checklist baru.</span></div></td></tr>
                @endforelse
            </tbody></table></div>
            <footer class="checklists-table-panel__footer"><span>Menampilkan {{ $items->count() }} item</span><span>Data tersimpan di database</span></footer>
        </section>
    </div>

    <dialog class="checklist-dialog" id="category-form-dialog" aria-labelledby="category-form-title">
        <form class="checklist-form" id="category-form" method="POST" action="{{ $categoryForm['action'] }}" data-store-action="{{ route('maintenance-categories.store') }}">
            @csrf <input data-method-field type="hidden" name="_method" value="{{ $categoryForm['method'] }}"><input type="hidden" name="record_id" value="{{ $categoryForm['recordId'] }}" data-record-id-field><input type="hidden" name="form_type" value="category">
            <header class="checklist-dialog__header"><div class="checklist-dialog__title"><span><i data-lucide="folder-plus"></i></span><div><p>Master kategori</p><h2 id="category-form-title" data-category-form-title>{{ $categoryForm['isEditing'] ? 'Edit Kategori' : 'Tambah Kategori' }}</h2></div></div><button class="checklist-dialog__close" type="button" data-dialog-close aria-label="Tutup"><i data-lucide="x"></i></button></header>
            <div class="checklist-form__body"><div class="checklist-form__grid">
                <div class="form-field checklist-form__field--wide"><label for="category-name">Nama kategori</label><input class="form-control" id="category-name" name="name" value="{{ old('form_type') === 'category' ? old('name') : '' }}" required></div>
                <div class="form-field"><label for="category-frequency">Frekuensi</label><select class="form-control" id="category-frequency" name="frequency" required>@foreach ($frequencyOptions as $value => $label)<option value="{{ $value }}" @selected(old('form_type') === 'category' && old('frequency') === $value)>{{ $label }}</option>@endforeach</select></div>
                <div class="form-field"><label for="category-status">Status</label><select class="form-control" id="category-status" name="status" required>@foreach ($statusOptions as $value => $label)<option value="{{ $value }}" @selected(old('form_type') === 'category' && old('status', 'active') === $value)>{{ $label }}</option>@endforeach</select></div>
                <div class="form-field checklist-form__field--wide"><label for="category-description">Deskripsi</label><textarea class="form-control" id="category-description" name="description" rows="4">{{ old('form_type') === 'category' ? old('description') : '' }}</textarea></div>
            </div></div>
            <footer class="checklist-form__footer"><button class="checklists-secondary-button" type="button" data-dialog-close>Batal</button><button class="checklists-primary-button" type="submit"><i data-lucide="save"></i>Simpan Kategori</button></footer>
        </form>
    </dialog>

    <dialog class="checklist-dialog" id="checklist-form-dialog" aria-labelledby="checklist-form-title">
        <form class="checklist-form" id="checklist-form" method="POST" action="{{ $itemForm['action'] }}" data-store-action="{{ route('maintenance-items.store') }}">
            @csrf <input data-method-field type="hidden" name="_method" value="{{ $itemForm['method'] }}"><input type="hidden" name="record_id" value="{{ $itemForm['recordId'] }}" data-record-id-field><input type="hidden" name="form_type" value="item">
            <header class="checklist-dialog__header"><div class="checklist-dialog__title"><span><i data-lucide="clipboard-plus"></i></span><div><p>Master item pemeriksaan</p><h2 id="checklist-form-title" data-checklist-form-title>{{ $itemForm['isEditing'] ? 'Edit Item Checklist' : 'Tambah Item Checklist' }}</h2></div></div><button class="checklist-dialog__close" type="button" data-dialog-close aria-label="Tutup"><i data-lucide="x"></i></button></header>
            <div class="checklist-form__body"><div class="checklist-form__grid">
                <div class="form-field"><label for="checklist-category">Kategori</label><select class="form-control" id="checklist-category" name="maintenance_category_id" required><option value="">Pilih kategori</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected(old('form_type') === 'item' && (string) old('maintenance_category_id') === (string) $category->id)>{{ $category->name }}</option>@endforeach</select></div>
                <div class="form-field"><label for="checklist-sort-order">Urutan</label><input class="form-control" id="checklist-sort-order" name="sort_order" type="number" min="0" max="65535" value="{{ old('form_type') === 'item' ? old('sort_order') : '' }}"></div>
                <div class="form-field checklist-form__field--wide"><label for="checklist-item-name">Nama item pemeriksaan</label><input class="form-control" id="checklist-item-name" name="item_name" value="{{ old('form_type') === 'item' ? old('item_name') : '' }}" required></div>
                <div class="form-field checklist-form__field--wide"><label for="checklist-criteria">Kriteria / standar</label><textarea class="form-control" id="checklist-criteria" name="criteria" rows="4" required>{{ old('form_type') === 'item' ? old('criteria') : '' }}</textarea></div>
                <div class="form-field"><label for="checklist-input-type">Tipe input</label><select class="form-control" id="checklist-input-type" name="input_type" required>@foreach ($inputTypeOptions as $value => $label)<option value="{{ $value }}" @selected(old('form_type') === 'item' && old('input_type') === $value)>{{ $label }}</option>@endforeach</select></div>
                <div class="form-field"><label for="checklist-status">Status</label><select class="form-control" id="checklist-status" name="status" required>@foreach ($statusOptions as $value => $label)<option value="{{ $value }}" @selected(old('form_type') === 'item' && old('status', 'active') === $value)>{{ $label }}</option>@endforeach</select></div>
            </div></div>
            <footer class="checklist-form__footer"><button class="checklists-secondary-button" type="button" data-dialog-close>Batal</button><button class="checklists-primary-button" type="submit"><i data-lucide="save"></i>Simpan Item</button></footer>
        </form>
    </dialog>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('assets/js/checklists.js') }}"></script>
@endpush
