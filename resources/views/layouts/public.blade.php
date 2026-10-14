@extends('layouts.base')
@section('body-class', 'home-page')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}?v={{ filemtime(public_path('css/home.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/commerce.css') }}">
@endpush
@section('body')
    <x-navbar :settings="$settings" />
    <main id="main" class="@yield('main-class', 'portal-main member-main')">@yield('content')</main>
    <x-footer :settings="$settings" />
@endsection
