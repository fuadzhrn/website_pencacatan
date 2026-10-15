@extends('layouts.app')

@section('title', 'Jadwal Maintenance')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/schedules.css') }}">
@endpush

@section('content')
    @include('schedules._database-content')
@endsection
