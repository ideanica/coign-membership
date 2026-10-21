<div class="tracking-page public-tracking-result" aria-live="polite">
    <div class="tracking-section-heading"><h2>{{ __('Perjalanan pesananmu') }}</h2><span>{{ $result['events']->count() }} {{ __('update dari tim') }}</span></div>
    <section class="neo-card journey-hero">
        <div class="journey-hero-main"><span class="tracking-kicker">{{ __('PERKEMBANGAN PESANAN') }}</span><h2>{{ $result['title'] }}</h2><p><x-icon name="gift" size="18" /> {{ $result['service'] }}</p><span class="public-order-code">{{ $code }}</span></div>
        <div class="journey-hero-status"><span class="tracking-kicker">{{ __('STATUS SAAT INI') }}</span><span class="badge status-badge" style="--status-color:{{ $result['color'] }}">{{ __($result['status']) }}</span><p>{{ $result['closed_at'] ? __('Tracking pesanan sudah ditutup.') : __('Pesananmu sedang dalam perjalanan.') }}</p></div>
    </section>
    <div class="journey-detail-grid">
        <section class="neo-card journey-timeline-panel">
            <div class="journey-panel-heading"><span class="journey-icon"><x-icon name="spark" size="22" /></span><div><h2>{{ __('Setiap langkah, tercatat.') }}</h2><p>{{ __('Status terbaru tampil paling atas.') }}</p></div><span class="badge">{{ $result['events']->count() }} update</span></div>
            <ol class="journey-timeline">
                @forelse($result['events'] as $event)
                    <li class="{{ $loop->first ? 'is-latest' : '' }}"><span class="journey-step" style="--status-color:{{ $event['color'] }}">{{ $result['events']->count() - $loop->index }}</span><div class="journey-event"><div class="journey-event-date"><time datetime="{{ $event['date']->toIso8601String() }}">{{ $event['date']->locale(app()->getLocale())->translatedFormat('d F Y') }} <span>{{ $event['date']->format('H:i') }}</span></time>@if($loop->first)<span class="latest-label">{{ __('TERBARU') }}</span>@endif</div><span class="badge status-badge" style="--status-color:{{ $event['color'] }}">{{ __($event['label']) }}</span></div></li>
                @empty
                    <li><span class="journey-step"><x-icon name="calendar" size="18" /></span><div class="journey-event"><h3>{{ __('Menunggu kabar pertama') }}</h3><p>{{ __('Update dari tim akan muncul di sini setelah pesanan diproses.') }}</p></div></li>
                @endforelse
            </ol>
            <div class="journey-timeline-foot"><x-icon name="info" size="16" /><span>{{ __('Setiap update mencatat status pesanan pada saat itu.') }}</span></div>
        </section>
        <aside class="journey-sidebar">
            <section class="neo-card journey-target"><span class="tracking-kicker"><x-icon name="calendar" size="17" /> {{ __('TARGET SELESAI') }}</span><strong>{{ $result['due_date']?->locale(app()->getLocale())->translatedFormat('d F Y') ?? __('Belum ditentukan') }}</strong><p>{{ $result['closed_at'] ? __('Ditutup pada').' '.$result['closed_at']->locale(app()->getLocale())->translatedFormat('d F Y').'.' : __('Perubahan jadwal akan diinformasikan oleh pengelola.') }}</p></section>
            <section class="neo-card journey-info"><h2>{{ __('Update terakhir') }}</h2><p class="public-latest-time">{{ data_get($result['events']->first(), 'date')?->locale(app()->getLocale())->translatedFormat('d F Y') ?? __('Belum ada update') }}</p><p class="journey-info-note">{{ __('Ada pertanyaan tentang proses atau jadwal pesananmu?') }}</p><a class="button button-neutral" href="{{ route('home').'#kontak' }}">{{ __('Hubungi pengelola') }} <x-icon name="arrow-up-right" size="17" /></a></section>
        </aside>
    </div>
</div>
