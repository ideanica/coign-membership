@extends('layouts.public')
@section('title', __('Web Development & Packages'))
@section('meta-description', __('Website profesional dan custom web system untuk bisnis. Bandingkan paket Starter, Business, Business Pro, dan Custom System dengan harga serta scope yang transparan.'))
@section('main-class', 'web-development-main')
@push('styles')<link rel="stylesheet" href="{{ asset('css/web-development.css') }}?v={{ filemtime(public_path('css/web-development.css')) }}">@endpush
@section('content')
    @include('web-development.hero')
    <section class="home-container web-section" id="packages" aria-label="Web Development Packages">
        <x-section-heading eyebrow="PACKAGES FOR YOUR NEXT STEP" title="Pilih langkah yang pas untuk bisnismu.">{{ __('Dari website pertama hingga sistem bisnis yang dirancang khusus. Scope jelas, tanpa fitur yang tidak kamu butuhkan.') }}</x-section-heading>
        <div class="web-pricing-grid">@foreach($packages as $package)<x-pricing-card :package="$package" :settings="$settings" />@endforeach</div>
        <p class="web-section-note">{{ __('Harga Founding Client telah dibulatkan untuk kebutuhan marketing. Berlaku untuk 5 proyek pertama; konfirmasi ketersediaan saat diskusi. Tidak ada pengurangan fitur, revision, maupun post-launch support.') }}</p>
    </section>
    <section class="home-container web-section" aria-label="{{ __('Perbandingan paket') }}">
        <x-section-heading eyebrow="THE DETAILS, SIDE BY SIDE" title="Bandingkan sebelum memilih.">{{ __('Geser tabel untuk melihat seluruh paket. Fitur Custom System ditentukan dalam quotation, bukan seluruhnya termasuk dalam harga mulai.') }}</x-section-heading>
        <x-package-comparison :rows="$comparison" />
    </section>
    <section class="home-container web-section" aria-label="Digital Presence Review">
        <div class="card web-review-card"><div><span class="section-kicker">A CLEARER START</span><h2>Digital Presence Review</h2><p>{{ __('Bonus untuk Business dan Business Pro. Review singkat agar struktur website berangkat dari kebutuhan bisnis yang nyata.') }}</p></div><div><x-feature-list :items="['Kelengkapan Google Business Profile', 'Website / digital presence saat ini', 'Beberapa competitor websites', 'Rekomendasi struktur website yang relevan']" /><p class="web-small">{{ __('Review ini tidak menjanjikan ranking SEO maupun Google Maps.') }}</p></div></div>
    </section>
    <section class="home-container web-section" aria-label="Optional Add-ons">
        <x-section-heading eyebrow="ROOM TO GROW" title="Optional Add-ons.">{{ __('Tambahkan kebutuhan yang relevan. Harga dapat berubah berdasarkan kompleksitas implementasi.') }}</x-section-heading>
        <dl class="card web-addon-grid">@foreach($addons as [$name, $price])<div><dt>{{ __($name) }}</dt><dd>{{ __('Mulai') }} {{ $price }}</dd></div>@endforeach</dl>
    </section>
    @include('web-development.process')
    @include('web-development.policies')
    <section class="home-container web-section" aria-label="FAQ">
        <x-section-heading eyebrow="A FEW GOOD QUESTIONS" title="Sebelum kita mulai.">{{ __('Jawaban singkat untuk pertanyaan yang sering ditanyakan.') }}</x-section-heading>
        <x-faq :items="__('web-development.faq')" />
    </section>
    <section class="home-container web-section web-final-cta" aria-label="{{ __('Diskusi proyek') }}">
        <div class="card"><span class="section-kicker">LET’S FIND YOUR NEXT STEP</span><h2>Not Sure Which Package Fits Your Business?</h2><p>{{ __('Ceritakan kebutuhan bisnis dan tujuan website Anda. Kami akan membantu menentukan scope yang paling sesuai tanpa memaksakan paket yang tidak dibutuhkan.') }}</p><div class="hero-actions"><x-project-cta :settings="$settings" /><a class="home-text-link" href="{{ route('home') }}#kontak">Contact Us <x-icon name="arrow-up-right" size="16" /></a></div></div>
    </section>
@endsection
