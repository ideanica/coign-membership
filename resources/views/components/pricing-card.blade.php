@props(['package', 'settings' => []])
<article {{ $attributes->class(['card web-pricing-card', 'web-pricing-recommended' => $package['recommended']]) }} aria-labelledby="package-{{ $package['id'] }}">
    <div class="web-package-main">
        <div class="web-package-topline">@if($package['recommended'])<span class="web-badge">Most Recommended</span>@else<span class="section-kicker">{{ $package['starting'] ? 'BUILT AROUND YOUR WORKFLOW' : 'BUILT FOR YOUR BUSINESS' }}</span>@endif</div>
        <h3 id="package-{{ $package['id'] }}">{{ $package['name'] }}</h3>
        <p class="web-package-title">{{ $package['title'] }}</p>
        <p class="web-package-description">{{ __($package['description']) }}</p>
        <div class="web-package-price">
            <p class="web-regular-price">{{ __('Regular price') }} <s>{{ $package['starting'] ? __('Mulai').' ' : '' }}{{ $package['regular'] }}</s></p>
            <span class="web-price-label">Founding Client Price</span>
            @if($package['starting'])<span class="web-price-from">{{ __('Mulai') }}</span>@endif
            <strong>{{ $package['price'] }}</strong>
        </div>
        <x-project-cta :settings="$settings" :package="$package['name']" :label="'Diskusikan paket :package'" class="web-package-cta" :aria-label="__('Diskusikan paket').' '.$package['name']" />
        <p class="web-package-timeline">{{ __('Estimasi') }} <strong>{{ __($package['timeline']) }}</strong></p>
    </div>
    <div class="web-package-features">
        <div class="web-package-includes"><h4>{{ $package['starting'] ? __('Possible features · sesuai scope') : __('Included') }}</h4><x-feature-list :items="$package['features']" /></div>
        <div class="web-package-support"><p>{{ __($package['revisions']) }}</p><p>{{ __($package['support']) }}</p></div>
    </div>
    <div class="web-package-details">
        @if($package['bonuses'])<div class="web-package-bonuses"><h4>Bonus</h4><x-feature-list :items="$package['bonuses']" />@if($package['id'] === 'business')<p>{{ __('Promotional Section untuk promo, seasonal campaign, paket layanan, treatment unggulan, atau special offer.') }}</p>@endif</div>@endif
        <div class="web-package-foot"><h4>{{ $package['starting'] ? __('Contoh scope awal') : __('Recommended for') }}</h4><p>{{ __($package['audience']) }}</p><p class="web-package-notice">{{ __($package['notice']) }}</p></div>
    </div>
</article>
