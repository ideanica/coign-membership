<article class="neo-card testimonial-card">
    <div class="testimonial-stars" aria-label="{{ $testimonial->rating }} dari 5 bintang">@foreach(range(1, 5) as $star)<x-icon name="star" size="16" class="{{ $star <= $testimonial->rating ? 'filled' : '' }}" />@endforeach</div>
    <blockquote>{{ $testimonial->comment }}</blockquote>
    <div class="testimonial-author"><span class="testimonial-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($testimonial->reviewer_name, 0, 1)) }}</span><div><strong>{{ $testimonial->reviewer_name }}</strong><span>{{ $testimonial->created_at->locale(app()->getLocale())->translatedFormat('d M Y') }}</span></div></div>
    @if($showCatalog ?? false)<a class="testimonial-template" href="{{ route('catalog.show', $testimonial->catalog->slug).'#testimonials' }}">{{ $testimonial->catalog->name }} <x-icon name="arrow-up-right" size="14" /></a>@endif
</article>
