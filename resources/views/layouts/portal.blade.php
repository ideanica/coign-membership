@extends('layouts.base')
@section('robots', 'noindex, nofollow')
@section('body-class', request()->routeIs('admin.*') ? 'admin-page' : '')
@push('styles')<link rel="stylesheet" href="{{ asset('css/portal.css') }}">@endpush
@if(request()->routeIs('admin.*'))
@push('styles')<link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">@endpush
@push('scripts')<script src="{{ asset('js/admin-navigation.js') }}?v={{ filemtime(public_path('js/admin-navigation.js')) }}" defer></script>@endpush
@endif
@push('scripts')<script src="{{ asset('js/portal.js') }}" defer></script>@endpush
@section('body')
    @php($adminArea = request()->routeIs('admin.*'))
    @if($adminArea)
        <div class="admin-shell">
            @include('shared.admin-sidebar')
            <button type="button" class="admin-sidebar-backdrop" data-sidebar-close aria-label="Tutup menu admin" tabindex="-1" hidden></button>
            <div class="admin-workspace">
                <header class="portal-topbar"><button type="button" class="button button-neutral admin-sidebar-toggle" data-sidebar-toggle aria-controls="admin-sidebar" aria-expanded="false" aria-label="Buka menu admin"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg><span>Menu</span></button><p><strong>Admin panel</strong><span> Semua operasional, satu tempat.</span></p><div class="inline-actions"><a href="{{ route('admin.settings.edit') }}" class="small-link">Akun & pengaturan <x-icon name="arrow-up-right" size="15" /></a>@include('shared.logout')</div></header>
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
