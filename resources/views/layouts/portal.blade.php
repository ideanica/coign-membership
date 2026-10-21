@extends('layouts.base')
@section('robots', 'noindex, nofollow')
@section('body-class', request()->routeIs('admin.*') ? 'admin-page' : '')
@push('styles')<link rel="stylesheet" href="{{ asset('css/portal.css') }}">@endpush
@if(request()->routeIs('admin.*'))
@push('styles')<link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">@endpush
@push('scripts')<script src="{{ asset('js/admin-navigation.js') }}" defer></script>@endpush
@endif
@push('scripts')<script src="{{ asset('js/portal.js') }}" defer></script>@endpush
@section('body')
    @php($adminArea = request()->routeIs('admin.*'))
    @if($adminArea)
        <div class="admin-shell">
            @include('shared.admin-sidebar')
            <div class="admin-workspace">
                <header class="portal-topbar"><p><strong>Admin panel</strong><span> Semua operasional, satu tempat.</span></p><div class="inline-actions"><a href="{{ route('admin.settings.edit') }}" class="small-link">Akun & pengaturan <x-icon name="arrow-up-right" size="15" /></a>@include('shared.logout')</div></header>
    @else
        <x-navbar variant="member" :settings="$settings" />
    @endif
    <main id="main" class="portal-main {{ $adminArea ? 'admin-main' : 'member-main' }}">
        <div class="portal-heading"><div><p class="eyebrow">{{ $adminArea ? 'ADMIN SPACE' : __('YOUR MEMBER SPACE') }}</p><h1>@yield('title')<span class="heading-dot">.</span></h1><p class="page-description">@yield('description')</p></div><div class="inline-actions">@yield('actions')</div></div>
        @include('shared.alerts')
        @yield('content')
        @if($adminArea)<x-footer variant="compact" :settings="$settings" />@endif
    </main>
    @if($adminArea)</div></div>@else<x-footer variant="member" :settings="$settings" />@endif
    @include('shared.confirm-dialog')
@endsection
