@extends('layouts.public')
@section('title', 'Katalog undangan & ucapan')
@section('meta-description', 'Temukan template website undangan dan ucapan untuk pernikahan, ulang tahun, anniversary, dan momen spesial lainnya.')
@section('main-class', 'catalog-public-main')
@push('styles')<link rel="stylesheet" href="{{ asset('css/catalog.css') }}?v={{ filemtime(public_path('css/catalog.css')) }}">@endpush
@push('scripts')<script src="{{ asset('js/catalog-browser.js') }}?v={{ filemtime(public_path('js/catalog-browser.js')) }}" defer></script>@endpush
@section('content')
<section class="neo-card catalog-intro-banner" aria-labelledby="catalog-intro-title">
    <div class="catalog-intro-copy">
        <h1 id="catalog-intro-title">Momen spesialmu<br>sudah <span>punya web?</span></h1>
        <p>Pilih desainnya. Kita buat jadi cerita versimu.</p>
    </div>
    <div class="catalog-banner-logo"><x-brand-logo full :settings="$settings" /></div>
</section>
<section id="templates" class="catalog-browser" aria-label="Temukan template">
<div class="catalog-section-heading"><h2>Temukan yang cocok <span class="badge" data-catalog-count>{{ $catalogs->total() }}</span></h2><p>Undangan & ucapan digital, dibuat personal.</p></div>
<nav class="catalog-sort-tabs" aria-label="Urutan template">
    @foreach(['featured' => 'Favorit studio', 'latest' => 'Terbaru', 'price_low' => 'Harga termurah', 'price_high' => 'Harga tertinggi'] as $sort => $label)
        <a class="badge {{ request('sort', 'featured') === $sort ? 'selected' : '' }}" href="{{ route('catalog.index', [...request()->only(['q', 'category']), 'sort' => $sort]).'#templates' }}" @if(request('sort', 'featured') === $sort) aria-current="page" @endif>
            @if($sort === 'featured')<x-icon name="star" size="15" />@endif
            {{ $label }}
        </a>
    @endforeach
</nav>
<form class="neo-card catalog-filter" method="get" action="{{ route('catalog.index') }}#templates">
    <input type="hidden" name="sort" value="{{ request('sort', 'featured') }}">
    <input type="hidden" name="per_page" value="{{ $catalogs->perPage() }}">
    <div class="catalog-search"><x-icon name="search" size="18" /><label class="sr-only" for="catalog-q">Cari template</label><input id="catalog-q" name="q" value="{{ request('q') }}" maxlength="100" placeholder="Cari nama template..."></div>
    <button class="button catalog-filter-toggle" type="button" aria-controls="catalog-filter-options" aria-expanded="false" aria-label="Buka filter katalog" hidden><x-icon name="filter" size="18" /></button>
    <div id="catalog-filter-options" class="catalog-filter-options">
    <div class="catalog-category-filter"><label class="sr-only" for="catalog-category">Kategori momen</label><select id="catalog-category" name="category"><option value="">Semua kategori momen</option>
        @foreach($categories as $category)
            <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>
        @endforeach
    </select></div>
    <button class="button" type="submit">Terapkan filter <x-icon name="search" size="16" /></button>
    <a class="small-link" data-catalog-reset href="{{ route('catalog.index', ['sort' => request('sort', 'featured')]).'#templates' }}" @if(!request('q') && !request('category')) hidden @endif>Reset filter</a>
    </div>
</form>
<p class="catalog-load-status" role="status" aria-live="polite" data-catalog-status></p>
<div data-catalog-results aria-busy="false">@include('catalog.partials.results')</div>
</section>
<section class="neo-card catalog-custom-banner"><span class="catalog-custom-icon"><x-icon name="spark" size="32" /></span><div><p class="eyebrow">MAKE IT YOURS</p><h2>Punya konsep sendiri?</h2><p>Ceritakan idemu. Kita bantu pilih desain dan personalisasi yang cocok untuk momenmu.</p></div><a class="button button-neutral" href="{{ route('home').'#kontak' }}">Yuk, ngobrol <x-icon name="arrow-up-right" size="18" /></a></section>
@endsection
