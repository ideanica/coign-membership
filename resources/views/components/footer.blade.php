@props(['variant' => 'public', 'settings' => []])
@if($variant === 'compact')
    <footer class="portal-footer"><span>© {{ date('Y') }} {{ $settings['brand_name'] ?? 'Coignitive' }}. Good things add up.</span>@if(filled($settings['support_email'] ?? ''))<a href="mailto:{{ $settings['support_email'] }}">Hubungi pengelola</a>@endif</footer>
@else
    <footer class="{{ $variant === 'public' ? 'home-footer' : 'site-footer' }}"><div class="{{ $variant === 'public' ? 'home-container' : 'footer-inner' }}"><span>© {{ date('Y') }} {{ $settings['brand_name'] ?? 'Coignitive' }}.</span><a href="{{ route('home') }}">Home</a>@if(filled($settings['support_email'] ?? ''))<a href="mailto:{{ $settings['support_email'] }}">Hubungi pengelola</a>@endif<span>Good things add up.</span></div></footer>
@endif
