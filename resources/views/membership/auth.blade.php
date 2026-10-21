@extends('layouts.auth')
@section('title', 'Masuk member')
@push('styles')<link rel="stylesheet" href="{{ asset('css/auth-login.css') }}?v={{ filemtime(public_path('css/auth-login.css')) }}">@endpush
@section('content')
    @if(session('status'))<p class="auth-errors" role="status">{{ session('status') }}</p>@endif
    <section class="neo-card auth-card" aria-labelledby="auth-title">
        <div class="auth-card-heading"><span class="badge member-badge"><x-icon name="star" size="14" /> YOUR MEMBER SPACE</span><h1 id="auth-title">Selamat datang lagi.</h1><p>Masuk dengan akun yang dibuat oleh admin untuk melihat profil, saldo poin, dan aktivitasmu.</p></div>
        <form method="post" action="{{ route('login.store') }}" class="auth-form">
            @csrf
            @if($errors->any())<div class="auth-errors" role="alert"><strong>Periksa kembali datamu.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <div class="auth-field"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" placeholder="nama@email.com" maxlength="254" required @error('email') aria-invalid="true" @enderror></div>
            <div class="auth-field"><label for="password">Kata sandi</label><input id="password" name="password" type="password" autocomplete="current-password" required @error('password') aria-invalid="true" @enderror></div>
            <label class="remember-field"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
            <button type="submit" class="button auth-submit">Masuk ke akun <x-icon name="arrow-right" size="18" /></button>
        </form>
        @if($googleLoginEnabled)
            <p class="auth-switch">atau masuk lebih cepat</p>
            <a href="{{ route('auth.google.redirect') }}" class="button button-neutral auth-submit google-auth-button">
                <svg class="google-auth-icon" viewBox="0 0 18 18" aria-hidden="true" focusable="false">
                    <path fill="#4285F4" d="M17.64 9.2045c0-.638-.0573-1.2518-.1636-1.8409H9v3.4818h4.8436c-.2086 1.125-.8427 2.0782-1.7963 2.7154v2.2582h2.9082c1.702-1.5668 2.6845-3.8741 2.6845-6.6145z"/>
                    <path fill="#34A853" d="M9 18c2.43 0 4.4673-.8064 5.9564-2.1809l-2.9082-2.2582c-.8064.54-1.8368.8591-3.0482.8591-2.3441 0-4.3286-1.5845-5.0373-3.7104H.9564v2.3327A9 9 0 0 0 9 18z"/>
                    <path fill="#FBBC05" d="M3.9627 10.7096A5.41 5.41 0 0 1 3.6818 9c0-.5936.1018-1.1709.2809-1.7096V4.9577H.9564A9 9 0 0 0 0 9c0 1.4523.3477 2.8273.9564 4.0423l3.0063-2.3327z"/>
                    <path fill="#EA4335" d="M9 3.5795c1.3214 0 2.5077.4541 3.4427 1.3459l2.5814-2.5814C13.4636.8918 11.4264 0 9 0A9 9 0 0 0 .9564 4.9577l3.0063 2.3327C4.6714 5.1641 6.6559 3.5795 9 3.5795z"/>
                </svg>
                <span class="google-auth-label">Lanjutkan dengan Google</span>
            </a>
        @endif
        <p class="auth-switch">Belum punya akun? Hubungi pengelola untuk dibuatkan akun member.</p>
        <p class="auth-switch"><a href="{{ route('home') }}">Kembali ke Home</a></p>
    </section>
@endsection
