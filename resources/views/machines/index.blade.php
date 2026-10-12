@extends('layouts.app')

@section('title', 'Data Mesin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/machines.css') }}">
@endpush

@section('content')
    <div class="machines-page">
        @if (session('success'))
            <div class="alert alert--success" role="status">
                <i data-lucide="circle-check" aria-hidden="true"></i>
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert--danger" role="alert">
                <i data-lucide="triangle-alert" aria-hidden="true"></i>
                {{ session('error') }}
            </div>
        @endif

        <header class="machines-page-header">
            <div>
                <p class="machines-page-header__eyebrow">Master Data Peralatan</p>
                <h2>Data Mesin</h2>
                <p>Kelola data mesin/peralatan X-Ray yang digunakan untuk preventive maintenance.</p>
            </div>
            <a class="button button--primary" href="{{ route('machines.create') }}">
                <i data-lucide="plus" aria-hidden="true"></i>
                Tambah Mesin
            </a>
        </header>

        <section class="machines-summary" aria-label="Ringkasan data mesin">
            <article class="machines-summary-card machines-summary-card--total">
                <span class="machines-summary-card__icon" aria-hidden="true"><i data-lucide="scan-line"></i></span>
                <div><p>Total Mesin</p><strong>{{ $summary['total'] }}</strong><span>Seluruh peralatan tercatat</span></div>
            </article>
            <article class="machines-summary-card machines-summary-card--active">
                <span class="machines-summary-card__icon" aria-hidden="true"><i data-lucide="circle-check-big"></i></span>
                <div><p>Mesin Aktif</p><strong>{{ $summary['active'] }}</strong><span>Siap digunakan</span></div>
            </article>
            <article class="machines-summary-card machines-summary-card--maintenance">
                <span class="machines-summary-card__icon" aria-hidden="true"><i data-lucide="wrench"></i></span>
                <div><p>Dalam Maintenance</p><strong>{{ $summary['maintenance'] }}</strong><span>Sedang ditangani</span></div>
            </article>
            <article class="machines-summary-card machines-summary-card--inactive">
                <span class="machines-summary-card__icon" aria-hidden="true"><i data-lucide="circle-off"></i></span>
                <div><p>Nonaktif</p><strong>{{ $summary['inactive'] }}</strong><span>Tidak digunakan</span></div>
            </article>
        </section>

        <section class="machines-table-panel" aria-labelledby="machines-filter-title">
            <header class="machines-table-panel__header">
                <div><h2 id="machines-filter-title">Filter Mesin</h2><p>Cari berdasarkan nama, serial number, lokasi, status, atau bandar udara.</p></div>
            </header>
            <form class="machine-filter-form" method="GET" action="{{ route('machines.index') }}">
                <div class="machine-form__grid">
                    <div class="form-field">
                        <label for="machine-search">Nama, serial number, atau lokasi</label>
                        <input class="form-control" id="machine-search" name="search" type="search" value="{{ $filters['search'] }}" placeholder="Cari data mesin">
                    </div>
                    <div class="form-field">
                        <label for="machine-filter-status">Status</label>
                        <select class="form-control" id="machine-filter-status" name="status">
                            <option value="">Semua status</option>
                            @foreach ($statusOptions as $statusValue => $statusLabel)
                                <option value="{{ $statusValue }}" @selected($filters['status'] === $statusValue)>{{ $statusLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="machine-filter-airport">Bandar udara</label>
                        <select class="form-control" id="machine-filter-airport" name="airport">
                            <option value="">Semua bandar udara</option>
                            @foreach ($airports as $airport)
                                <option value="{{ $airport }}" @selected($filters['airport'] === $airport)>{{ $airport }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="machine-filter-actions">
                    <button class="button button--primary" type="submit"><i data-lucide="search" aria-hidden="true"></i>Terapkan</button>
                    <a class="button button--neutral" href="{{ route('machines.index') }}">Reset</a>
                </div>
            </form>
        </section>

        <section class="machines-table-panel" aria-labelledby="machines-table-title">
            <header class="machines-table-panel__header">
                <div>
                    <h2 id="machines-table-title">Daftar Mesin / Peralatan</h2>
                    <p>Data inventaris mesin X-Ray yang terdaftar pada sistem.</p>
                </div>
                <span class="machines-table-panel__count">{{ $machines->total() }} data</span>
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
                        @forelse ($machines as $managedMachine)
                            <tr>
                                <td><span class="machines-table__number">{{ str_pad((string) ($machines->firstItem() + $loop->index), 2, '0', STR_PAD_LEFT) }}</span></td>
                                <td>
                                    <div class="machines-equipment">
                                        <span aria-hidden="true"><i data-lucide="scan-line"></i></span>
                                        <div>
                                            <strong>{{ $managedMachine->equipment_name }}</strong>
                                            <small>{{ $managedMachine->description ?: 'Peralatan X-Ray' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $managedMachine->brand }}</td>
                                <td>{{ $managedMachine->type }}</td>
                                <td><code>{{ $managedMachine->serial_number }}</code></td>
                                <td>{{ $managedMachine->location }}</td>
                                <td>{{ $managedMachine->airport }}</td>
                                <td><span class="machine-status machine-status--{{ $managedMachine->status }}">{{ $statusOptions[$managedMachine->status] }}</span></td>
                                <td>
                                    <div class="machines-actions">
                                        <a class="machine-action machine-action--detail" href="{{ route('machines.show', $managedMachine) }}"><i data-lucide="eye" aria-hidden="true"></i>Detail</a>
                                        <a class="machine-action machine-action--edit" href="{{ route('machines.edit', $managedMachine) }}"><i data-lucide="pencil" aria-hidden="true"></i>Edit</a>
                                        <form method="POST" action="{{ route('machines.toggle-status', $managedMachine) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="machine-action" type="submit"><i data-lucide="power" aria-hidden="true"></i>{{ $managedMachine->status === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('machines.destroy', $managedMachine) }}" onsubmit="return confirm('Hapus mesin ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="machine-action machine-action--delete" type="submit"><i data-lucide="trash-2" aria-hidden="true"></i>Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9">Tidak ada mesin yang sesuai dengan filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <footer class="machines-table-panel__footer">
                <span>Menampilkan {{ $machines->firstItem() ?? 0 }}&ndash;{{ $machines->lastItem() ?? 0 }} dari {{ $machines->total() }} mesin</span>
                @if ($machines->hasPages())
                    <span class="machine-pagination">
                        @if ($machines->onFirstPage())<span aria-disabled="true">Sebelumnya</span>@else<a href="{{ $machines->previousPageUrl() }}">Sebelumnya</a>@endif
                        <small>Halaman {{ $machines->currentPage() }} dari {{ $machines->lastPage() }}</small>
                        @if ($machines->hasMorePages())<a href="{{ $machines->nextPageUrl() }}">Berikutnya</a>@else<span aria-disabled="true">Berikutnya</span>@endif
                    </span>
                @endif
            </footer>
        </section>
    </div>
@endsection
