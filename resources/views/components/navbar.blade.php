@props(['variant' => 'public', 'settings' => []])
@php
    $public = $variant === 'public';
    $menus = $public ? [
        ['home', 'Home', 'home', null],
        ['catalog.index', 'Katalog', 'catalog.*', null],
        ['public.tracking.index', 'Tracking', 'public.tracking.*', null],
    ] : [
        ['home', 'Home', 'home', null],
        ['catalog.index', 'Katalog', 'catalog.*', null],
        ['membership.profile', 'Profil & poin', 'membership.profile', 'user'],
        ['membership.tracking.index', 'Tracking', 'membership.tracking.*', 'calendar'],
        ['membership.bills.index', 'Bill', 'membership.bills.*', 'receipt'],
    ];
@endphp
<header class="site-header clean-navbar {{ $public ? 'home-header' : 'member-header' }}">
    <div class="header-inner">
        <x-brand-link class="navbar-brand" :dot="true" :settings="$settings" size="80" :caption="$public ? 'by acalysis' : 'MEMBERSHIP'" />
        <button class="button navbar-toggle" type="button" aria-controls="site-navigation site-account-actions" aria-expanded="false" aria-label="Buka menu navigasi" hidden><span data-nav-toggle-label>Menu</span><span class="navbar-toggle-bars" aria-hidden="true"><i></i><i></i><i></i></span></button>
        <div class="navbar-dropdown">
        <nav id="site-navigation" class="main-nav" aria-label="{{ $public ? 'Navigasi utama' : 'Menu member' }}">
            @foreach($menus as [$route, $label, $pattern, $icon])
                @php($active = request()->routeIs(...(array) $pattern))
                <a class="nav-link {{ $active ? 'active' : '' }}" href="{{ route($route) }}" @if($active) aria-current="{{ request()->routeIs($route) ? 'page' : 'location' }}" @endif>@if($icon)<x-icon :name="$icon" size="17" />@endif{{ $label }}</a>
            @endforeach
            @if(!$public && auth()->user()?->is_admin)<a class="nav-link" href="{{ route('admin.dashboard') }}">Admin</a>@endif
        </nav>
        <div id="site-account-actions" class="{{ $public ? 'home-auth' : 'inline-actions' }}">
            @if($public)
                <a class="button button-neutral" href="{{ auth()->check() ? route(auth()->user()->is_admin ? 'admin.dashboard' : 'membership.profile') : route('login') }}">{{ auth()->check() ? (auth()->user()->is_admin ? 'Admin panel' : 'Akun member') : 'Masuk' }} <x-icon name="arrow-up-right" size="16" /></a>
            @else
                @include('shared.logout')
            @endif
        </div>
        </div>
    </div>
</header>
