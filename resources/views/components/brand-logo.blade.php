@props(['full' => false, 'size' => 40, 'settings' => []])
@if($full)
    <img class="brand-logo-full" src="{{ asset('images/Untitled27_20261014062958.png') }}" alt="{{ $settings['brand_name'] ?? 'Coignitive' }} — by @acalysis" width="1600" height="1600">
@else
    <img class="brand-logo-cat" src="{{ asset('images/Untitled27_20261014014436.png') }}" width="{{ $size }}" height="{{ $size }}" style="--brand-logo-size:{{ $size }}px" alt="">
@endif
