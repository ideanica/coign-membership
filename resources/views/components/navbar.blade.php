@props(['variant' => 'public', 'settings' => []])
@php
    $public = ! auth()->check();
    $menus = ($public || auth()->user()?->is_admin) ? [
        ['home', 'Home', 'home', null],
        ['catalog.index', 'Katalog', 'catalog.*', null],
        ['public.tracking.index', 'Tracking', 'public.tracking.*', null],
    ] : [
        ['home', 'Home', 'home', null],
        ['catalog.index', 'Katalog', 'catalog.*', null],
        ['membership.profile', 'Profil & poin', 'membership.profile', 'user'],
        ['membership.tracking.index', 'Pesanan', 'membership.tracking.*', 'calendar'],
    ];
@endphp
<header class="site-header clean-navbar">
    <div class="header-inner">
        <x-brand-link class="navbar-brand" :dot="true" :settings="$settings" size="80" caption="IT Creative Workspace" />
        <button class="button navbar-toggle" type="button" aria-controls="site-navigation site-account-actions" aria-expanded="false" aria-label="{{ __('Buka menu navigasi') }}" hidden><span data-nav-toggle-label>Menu</span><span class="navbar-toggle-bars" aria-hidden="true"><i></i><i></i><i></i></span></button>
        <div class="navbar-dropdown">
        <nav id="site-navigation" class="main-nav" aria-label="{{ $public ? __('Navigasi utama') : (auth()->user()?->is_admin ? __('Navigasi admin') : __('Menu member')) }}">
            @foreach($menus as [$route, $label, $pattern, $icon])
                @php($active = request()->routeIs(...(array) $pattern))
                <a class="nav-link {{ $active ? 'active' : '' }}" href="{{ route($route) }}" @if($active) aria-current="{{ request()->routeIs($route) ? 'page' : 'location' }}" @endif>@if($icon)<x-icon :name="$icon" size="17" />@endif{{ __($label) }}</a>
            @endforeach
            @if(!$public && auth()->user()?->is_admin)<a class="nav-link" href="{{ route('admin.dashboard') }}">Admin</a>@endif
        </nav>
        <div id="site-account-actions" class="home-auth">
            @if($public)
                <a class="button button-neutral" href="{{ route('login') }}">{{ __('Masuk') }} <x-icon name="arrow-up-right" size="16" /></a>
            @else
                @if(!auth()->user()?->is_admin)<a class="button button-neutral" href="{{ route('membership.account.security') }}">{{ __('Keamanan akun') }}</a>@endif
                @include('shared.logout')
            @endif
        </div>
        </div>
    </div>
</header>
