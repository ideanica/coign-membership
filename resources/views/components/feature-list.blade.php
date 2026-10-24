@props(['items'])
<ul {{ $attributes->class(['web-feature-list']) }}>
    @foreach($items as $item)<li><span aria-hidden="true">✓</span><span>{{ __($item) }}</span></li>@endforeach
</ul>
