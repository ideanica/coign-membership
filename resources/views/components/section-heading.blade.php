@props(['eyebrow', 'title'])
<div {{ $attributes->class(['web-section-heading']) }}>
    <span class="section-kicker">{{ __($eyebrow) }}</span>
    <h2>{{ __($title) }}</h2>
    @if($slot->isNotEmpty())<p>{{ $slot }}</p>@endif
</div>
