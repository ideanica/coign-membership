@extends('layouts.auth')
@section('title', 'Masuk member')
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
        <p class="auth-switch">Belum punya akun? Hubungi pengelola untuk dibuatkan akun member.</p>
        <p class="auth-switch"><a href="{{ route('home') }}">Kembali ke Home</a></p>
    </section>
@endsection
