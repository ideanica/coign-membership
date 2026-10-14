@extends('layouts.public')
@section('title', e($catalog->name))
@section('meta-description', e(\Illuminate\Support\Str::limit($catalog->description, 160)))
@section('main-class', 'catalog-public-main catalog-detail-page')
@push('styles')<link rel="stylesheet" href="{{ asset('css/catalog.css') }}?v={{ filemtime(public_path('css/catalog.css')) }}">@endpush
@push('scripts')<script src="{{ asset('js/catalog-gallery.js') }}?v={{ filemtime(public_path('js/catalog-gallery.js')) }}" defer></script>@endpush
@push('styles')<link rel="stylesheet" href="{{ asset('css/testimonials.css') }}?v={{ filemtime(public_path('css/testimonials.css')) }}">@endpush
@push('scripts')<script src="{{ asset('js/testimonials.js') }}?v={{ filemtime(public_path('js/testimonials.js')) }}" defer></script>@endpush
@section('content')
<nav class="catalog-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('catalog.index') }}"><x-icon name="chevron-left" size="16" /> Katalog</a><span>/</span><span aria-current="page">{{ $catalog->name }}</span></nav>
<div class="catalog-detail-grid">
    <section class="catalog-gallery" aria-label="Galeri template">
        <div class="neo-card catalog-gallery-main">
            <div class="catalog-window-bar"><span class="catalog-window-dots"><i></i><i></i><i></i></span><span>PREVIEW TEMPLATE</span><span data-gallery-counter>01 / {{ str_pad(max(1, $catalog->images->count()), 2, '0', STR_PAD_LEFT) }}</span></div>
            <div class="catalog-gallery-stage">
                @if($catalog->images->first())
                    <img data-gallery-main src="{{ $catalog->images->first()->url }}" alt="{{ $catalog->images->first()->alt_text ?: $catalog->name }}">
                @else
                    <div class="catalog-image-placeholder"><x-icon name="gift" size="48" /><span>Preview sedang disiapkan</span></div>
                @endif
            </div>
            <div class="catalog-gallery-caption"><x-icon name="heart" size="15" /><span>Desain pilihanmu. Cerita versimu.</span></div>
        </div>
        @if($catalog->images->count() > 1)
            <p class="catalog-gallery-help">Pilih gambar untuk melihat detail desain.</p>
            <div class="catalog-thumbnails">
                @foreach($catalog->images as $image)
                    <button class="catalog-thumb" type="button" data-gallery-src="{{ $image->url }}" data-gallery-alt="{{ $image->alt_text ?: $catalog->name }}" data-gallery-index="{{ $loop->iteration }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" aria-label="Tampilkan gambar {{ $loop->iteration }}"><img src="{{ $image->url }}" alt="" loading="lazy"></button>
                @endforeach
            </div>
        @endif
    </section>
    <section class="neo-card catalog-detail-copy">
        <p class="eyebrow">A WEB FOR YOUR MOMENT</p>
        <div class="catalog-tags">
            @foreach($catalog->categories->where('is_active', true) as $category)
                <a href="{{ route('catalog.index', ['category' => $category->slug]) }}">{{ $category->name }}</a>
            @endforeach
        </div>
        <h1>{{ $catalog->name }}</h1>
        <p class="catalog-detail-intro">Satu desain spesial, siap jadi bagian dari momenmu.</p>
        @if($catalog->reviews_count > 0)<p class="catalog-rating"><x-icon name="star" size="15" /> {{ number_format((float) $catalog->rating_average, 1, ',', '.') }} / 5 &middot; {{ $catalog->reviews_count }} ulasan</p>@endif
        @php($saving = $pricing->discountAmount($catalog))
        <div class="catalog-detail-price">
            <div class="catalog-price-heading"><span>Harga template</span><x-icon name="spark" size="24" /></div>
            @if($saving)
                <div class="catalog-price-before"><del>{{ $pricing->format($catalog->base_price) }}</del><span class="catalog-price-discount">{{ $catalog->discount->type === 'percent' ? $catalog->discount->value : (int) round($saving / max(1, $catalog->base_price) * 100) }}% OFF</span></div>
            @endif
            <strong class="catalog-price-desktop">{{ $pricing->format($pricing->finalPrice($catalog)) }}</strong>
            <strong class="catalog-price-mobile"><span class="catalog-price-currency">IDR<span class="catalog-price-dot">.</span></span><span class="catalog-price-amount">@foreach(explode('.', number_format($pricing->finalPrice($catalog), 0, ',', '.')) as $part)@unless($loop->first)<span class="catalog-price-dot">.</span>@endunless{{ $part }}@endforeach</span></strong>
            @if($saving)<p class="catalog-price-saving">Kamu hemat <b>{{ $pricing->format($saving) }}</b></p>@endif
        </div>
        <div class="catalog-delivery"><span class="catalog-delivery-icon"><x-icon name="calendar" size="20" /></span><span>Estimasi pengerjaan <strong>{{ $catalog->estimated_days }} hari</strong><small>Jadwal mulai disepakati saat konsultasi.</small></span></div>
        <div class="catalog-detail-actions">
            @if($catalog->preview_url)
                <a class="button catalog-demo-button" href="{{ $catalog->preview_url }}" target="_blank" rel="noopener noreferrer">Lihat demo langsung <x-icon name="arrow-up-right" size="18" /><span class="sr-only">Buka di tab baru</span></a>
            @endif
            <a class="button button-neutral" href="{{ filled($settings['support_email'] ?? '') ? 'mailto:'.$settings['support_email'].'?subject='.rawurlencode('Pesan template '.$catalog->name).'&body='.rawurlencode('Halo Coignitive, saya tertarik dengan template '.$catalog->name.'. Link: '.route('catalog.show', $catalog->slug)) : route('home').'#kontak' }}">Saya mau template ini <x-icon name="mail" size="17" /></a>
        </div>
        <p class="catalog-note"><x-icon name="heart" size="14" /> Kita diskusikan konsep dan kebutuhanmu sebelum mulai.</p>
    </section>
</div>
<nav class="catalog-detail-nav" aria-label="Bagian detail template"><a href="#template-details">Tentang desain</a><a href="#template-features">Fitur template</a><a href="#testimonials">Testimoni</a></nav>
<div class="catalog-detail-lower">
    <section id="template-details" class="neo-card catalog-about"><span class="badge"><x-icon name="heart" size="14" /> THE LITTLE DETAILS</span><h2>Kenalan dengan desainnya.</h2><p id="catalog-description" class="catalog-description">{{ $catalog->description }}</p><button class="catalog-description-toggle" type="button" aria-controls="catalog-description" aria-expanded="false" hidden><span data-description-label>Show more</span><x-icon name="arrow-up-right" size="15" /></button>
        @if($catalog->specifications->isNotEmpty())
            <details id="template-features" class="catalog-specifications" open><summary><h3>Yang ada di template ini</h3><x-icon name="chevron-down" size="18" /></summary><dl>
                @foreach($catalog->specifications as $spec)
                    <div><dt><x-icon name="check" size="15" /> {{ $spec->label }}</dt><dd>{{ $spec->value }}</dd></div>
                @endforeach
            </dl></details>
        @endif
    </section>
    <section class="neo-card catalog-order-guide"><p class="eyebrow">FROM TEMPLATE TO YOUR STORY</p><h2>Gimana cara pesannya?</h2><ol><li><span>01</span><div><h3>Temukan desainmu</h3><p>Lihat foto dan demo untuk mengenal tampilan template.</p></div></li><li><span>02</span><div><h3>Ceritakan momennya</h3><p>Kirim pilihan template, tanggal acara, dan konsep yang kamu inginkan.</p></div></li><li><span>03</span><div><h3>Kita buat jadi milikmu</h3><p>Detail, biaya personalisasi, dan jadwal disepakati sebelum pengerjaan.</p></div></li></ol><a class="small-link" href="{{ route('home').'#kontak' }}">Ada yang ingin ditanyakan? <x-icon name="arrow-up-right" size="16" /></a></section>
</div>
@include('catalog.partials.testimonials')
<section class="neo-card catalog-custom-banner"><span class="catalog-custom-icon"><x-icon name="gift" size="32" /></span><div><p class="eyebrow">FIND YOUR MATCH</p><h2>Masih cari desain yang pas?</h2><p>Jelajahi template lainnya dan temukan yang paling kamu suka.</p></div><a class="button button-neutral" href="{{ route('catalog.index') }}">Lihat katalog <x-icon name="arrow-right" size="18" /></a></section>
@endsection
