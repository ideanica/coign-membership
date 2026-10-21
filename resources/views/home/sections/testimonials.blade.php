@push('styles')<link rel="stylesheet" href="{{ asset('css/testimonials.css') }}?v={{ filemtime(public_path('css/testimonials.css')) }}">@endpush
<section class="home-container home-testimonials" id="testimonials">
    <div class="home-section-heading"><div><p class="eyebrow">{{ __('LITTLE WEBS, REAL STORIES') }}</p><h2>{{ __('Jadi bagian dari') }}<br><span>{{ __('cerita mereka.') }}</span></h2></div><p>{{ __('Pengalaman dari mereka yang sudah') }}<br>{{ __('memilih desain untuk momen spesialnya.') }}</p></div>
    @if($testimonialStats['total'])
        <div class="testimonial-home-stats"><span><x-icon name="star" size="18" /> <strong>{{ number_format($testimonialStats['average'], 1, ',', '.') }}/5</strong> {{ __('rata-rata rating') }}</span><span><strong>{{ $testimonialStats['total'] }}</strong> {{ __('testimoni dipublikasikan') }}</span><span><strong>{{ $testimonialStats['templates'] }}</strong> {{ __('template dinilai') }}</span></div>
        <div class="testimonial-home-grid">@foreach($testimonials as $testimonial)@include('testimonials.card', ['showCatalog' => true])@endforeach</div>
    @else
        <div class="neo-card testimonial-empty"><x-icon name="heart" size="28" /><h3>{{ __('Cerita pertamamu berarti.') }}</h3><p>{{ __('Bagikan pengalamanmu di halaman template yang kamu gunakan.') }}</p></div>
    @endif
    <a class="button button-neutral" href="{{ route('catalog.index') }}">{{ __('Temukan desain untuk ceritamu') }} <x-icon name="arrow-up-right" size="17" /></a>
</section>
