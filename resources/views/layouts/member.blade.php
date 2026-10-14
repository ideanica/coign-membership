@extends('layouts.base')
@section('robots', 'noindex, nofollow')
@push('styles')<link rel="stylesheet" href="{{ asset('css/portal.css') }}">@endpush
@section('body')
    <x-navbar variant="member" :settings="$settings" />
    <main id="main" class="@yield('main-class', 'page-container')">@yield('content')</main>
    <x-footer variant="member" :settings="$settings" />
@endsection
