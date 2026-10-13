@extends('layouts.app')

@section('title', 'Jadwal Maintenance')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/schedules.css') }}">
@endpush

@section('content')
    @include('schedules._database-content')
@endsection

@push('scripts')
    <script type="module" src="{{ asset('assets/js/schedules.js') }}"></script>
@endpush
