@extends('layouts.app')

@section('title', 'Tambah Mesin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/machines.css') }}">
@endpush

@section('content')
    <section class="machines-table-panel machine-record-page" aria-labelledby="create-machine-title">
        <header class="machines-table-panel__header">
            <div><h2 id="create-machine-title">Tambah Mesin</h2><p>Tambahkan mesin/peralatan X-Ray ke inventaris maintenance.</p></div>
            <a class="button button--neutral" href="{{ route('machines.index') }}">Kembali</a>
        </header>

        @if ($errors->any())
            <div class="alert alert--danger machine-record-alert" role="alert">
                <i data-lucide="triangle-alert" aria-hidden="true"></i>
                <div><strong>Data belum dapat disimpan.</strong><ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
            </div>
        @endif

        <form class="machine-record-form" method="POST" action="{{ route('machines.store') }}">
            @csrf
            <div class="machine-form__grid">
                <div class="form-field machine-form__field--wide"><label for="equipment_name">Nama Peralatan</label><input class="form-control" id="equipment_name" name="equipment_name" type="text" value="{{ old('equipment_name') }}" maxlength="255" required autofocus>@error('equipment_name')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div class="form-field"><label for="brand">Merk</label><input class="form-control" id="brand" name="brand" type="text" value="{{ old('brand') }}" maxlength="255" required>@error('brand')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div class="form-field"><label for="type">Tipe</label><input class="form-control" id="type" name="type" type="text" value="{{ old('type') }}" maxlength="255" required>@error('type')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div class="form-field"><label for="serial_number">Serial Number</label><input class="form-control" id="serial_number" name="serial_number" type="text" value="{{ old('serial_number') }}" maxlength="255" required>@error('serial_number')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div class="form-field"><label for="location">Lokasi</label><input class="form-control" id="location" name="location" type="text" value="{{ old('location') }}" maxlength="255" required>@error('location')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div class="form-field"><label for="airport">Bandar Udara</label><input class="form-control" id="airport" name="airport" type="text" value="{{ old('airport', 'UPBU Malikussaleh') }}" maxlength="255" required>@error('airport')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div class="form-field"><label for="status">Status</label><select class="form-control" id="status" name="status" required>@foreach ($statusOptions as $statusValue => $statusLabel)<option value="{{ $statusValue }}" @selected(old('status', 'active') === $statusValue)>{{ $statusLabel }}</option>@endforeach</select>@error('status')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div class="form-field machine-form__field--wide"><label for="description">Deskripsi <small>(opsional)</small></label><textarea class="form-control machine-record-textarea" id="description" name="description" maxlength="5000" rows="4">{{ old('description') }}</textarea>@error('description')<p class="form-error">{{ $message }}</p>@enderror</div>
            </div>
            <footer class="machine-record-actions"><a class="button button--neutral" href="{{ route('machines.index') }}">Batal</a><button class="button button--primary" type="submit"><i data-lucide="save" aria-hidden="true"></i>Simpan Mesin</button></footer>
        </form>
    </section>
@endsection
