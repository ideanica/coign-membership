@extends('layouts.public')
@section('title', 'Tracking pesanan')
@section('robots', 'noindex, nofollow')
@section('main-class', 'portal-main member-main public-tracking-page')
@push('styles')<link rel="stylesheet" href="{{ asset('css/tracking.css') }}?v={{ filemtime(public_path('css/tracking.css')) }}">@endpush
@section('content')
<section class="neo-card public-tracking-hero" aria-labelledby="tracking-title">
    <div class="public-tracking-intro">
        <span class="badge tracking-intro-label"><x-icon name="calendar" size="15" /> ORDER JOURNEY</span>
        <h1 id="tracking-title">{{ __('Satu kode.') }}<br><span>{{ __('Semua progres.') }}</span></h1>
        <p>{{ __('Dari ide pertama sampai project selesai. Pantau perjalanan pesananmu di sini, tanpa perlu login.') }}</p>
        <div class="tracking-intro-tags"><span><x-icon name="check" size="14" /> {{ __('Tanpa akun') }}</span><span><x-icon name="calendar" size="14" /> {{ __('Update dari tim') }}</span></div>
    </div>
    <div class="public-lookup">
        <div class="lookup-heading"><span class="journey-icon"><x-icon name="search" size="22" /></span><div><span class="tracking-kicker">{{ __('CEK PESANANMU') }}</span><h2>{{ __('Sudah sampai mana?') }}</h2></div></div>
        <form method="post" action="{{ route('public.tracking.lookup') }}">@csrf
            <label for="code">{{ __('Kode pesanan') }}</label>
            <div class="lookup-controls"><input id="code" name="code" value="{{ old('code', $code ?? '') }}" maxlength="24" placeholder="ORD-XXXXXXXXXXXXXXXX" autocomplete="off" autocapitalize="characters" spellcheck="false" aria-describedby="code-help @error('code') code-error @enderror" required @error('code') aria-invalid="true" @enderror><button class="button" type="submit">{{ __('Lacak pesanan') }} <x-icon name="arrow-right" size="18" /></button></div>
            <small id="code-help">{{ __('Masukkan kode yang diberikan pengelola setelah pesanan dibuat.') }}</small>
            @error('code')<p id="code-error" class="lookup-error" role="alert">{{ __('Masukkan kode pesanan dengan format ORD- diikuti 16 huruf atau angka.') }}</p>@enderror
        </form>
    </div>
</section>
@if(($searched ?? false) && !$result)
    <section class="neo-card journey-empty public-tracking-empty" role="status"><span class="journey-icon"><x-icon name="search" size="26" /></span><h2>{{ __('Pesanan belum ditemukan') }}</h2><p>{{ __('Periksa kembali kodenya, termasuk huruf dan angka. Jika kode sudah benar, hubungi pengelola untuk memastikan tracking publik sudah aktif.') }}</p><a class="button button-neutral" href="{{ route('home').'#kontak' }}">{{ __('Hubungi pengelola') }} <x-icon name="arrow-up-right" size="16" /></a></section>
@elseif($result ?? null)
    @include('tracking.public-result')
@else
    <div class="tracking-how" aria-label="Cara tracking"><div><span>01</span><p><strong>{{ __('Masukkan kode') }}</strong>{{ __('Salin kode dari pengelola ke kolom di atas.') }}</p></div><div><span>02</span><p><strong>{{ __('Lihat progres') }}</strong>{{ __('Cek status, riwayat, dan target selesai.') }}</p></div><div><span>03</span><p><strong>{{ __('Ikuti perjalanannya') }}</strong>{{ __('Kembali ke sini untuk kabar berikutnya.') }}</p></div></div>
    <div class="public-tracking-hint"><x-icon name="info" size="18" /><p>{{ __('Belum punya kode? Minta kepada pengelola. Simpan kode pesananmu dengan baik karena kode tersebut membuka akses tracking.') }}</p></div>
@endif
@endsection
