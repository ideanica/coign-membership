@props(['settings' => [], 'size' => 64, 'caption' => 'BY @ACALYSIS', 'destination' => null, 'dot' => false])
<a {{ $attributes->class(['brand']) }} href="{{ $destination ?? route('home') }}" aria-label="{{ $settings['brand_name'] ?? 'Coignitive' }}, halaman utama">
    <x-brand-logo :size="$size" :settings="$settings" />
    <span class="portal-brand-copy">{{ mb_strtolower($settings['brand_name'] ?? 'Coignitive') }}@if($dot)<span class="navbar-brand-dot" aria-hidden="true">.</span>@endif<span class="brand-caption">{{ $caption }}</span></span>
</a>
