@extends('layouts.base')
@section('robots', 'noindex, nofollow')
@section('body-class', 'auth-page')
@section('body')
    <main id="main" class="auth-container">
        <x-brand-link class="auth-brand" size="80" caption="MEMBERSHIP" :settings="$settings" />
        @yield('content')
        <p class="auth-tagline">Good things add up.</p>
    </main>
@endsection
