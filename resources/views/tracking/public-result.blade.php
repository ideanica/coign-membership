<div class="tracking-page public-tracking-result" aria-live="polite">
    <div class="tracking-section-heading"><h2>Perjalanan pesananmu</h2><span>{{ $result['events']->count() }} update dari tim</span></div>
    <section class="neo-card journey-hero">
        <div class="journey-hero-main"><span class="tracking-kicker">PERKEMBANGAN PESANAN</span><h2>{{ $result['title'] }}</h2><p><x-icon name="gift" size="18" /> {{ $result['service'] }}</p><span class="public-order-code">{{ $code }}</span></div>
        <div class="journey-hero-status"><span class="tracking-kicker">STATUS SAAT INI</span><span class="badge status-badge" style="--status-color:{{ $result['color'] }}">{{ $result['status'] }}</span><p>{{ $result['closed_at'] ? 'Tracking pesanan sudah ditutup.' : 'Pesananmu sedang dalam perjalanan.' }}</p></div>
    </section>
    <div class="journey-detail-grid">
        <section class="neo-card journey-timeline-panel">
            <div class="journey-panel-heading"><span class="journey-icon"><x-icon name="spark" size="22" /></span><div><h2>Setiap langkah, tercatat.</h2><p>Status terbaru tampil paling atas.</p></div><span class="badge">{{ $result['events']->count() }} update</span></div>
            <ol class="journey-timeline">
                @forelse($result['events'] as $event)
                    <li class="{{ $loop->first ? 'is-latest' : '' }}"><span class="journey-step" style="--status-color:{{ $event['color'] }}">{{ $result['events']->count() - $loop->index }}</span><div class="journey-event"><div class="journey-event-date"><time datetime="{{ $event['date']->toIso8601String() }}">{{ $event['date']->locale('id')->translatedFormat('d F Y') }} <span>{{ $event['date']->format('H:i') }}</span></time>@if($loop->first)<span class="latest-label">TERBARU</span>@endif</div><span class="badge status-badge" style="--status-color:{{ $event['color'] }}">{{ $event['label'] }}</span></div></li>
                @empty
                    <li><span class="journey-step"><x-icon name="calendar" size="18" /></span><div class="journey-event"><h3>Menunggu kabar pertama</h3><p>Update dari tim akan muncul di sini setelah pesanan diproses.</p></div></li>
                @endforelse
            </ol>
            <div class="journey-timeline-foot"><x-icon name="info" size="16" /><span>Setiap update mencatat status pesanan pada saat itu.</span></div>
        </section>
        <aside class="journey-sidebar">
            <section class="neo-card journey-target"><span class="tracking-kicker"><x-icon name="calendar" size="17" /> TARGET SELESAI</span><strong>{{ $result['due_date']?->locale('id')->translatedFormat('d F Y') ?? 'Belum ditentukan' }}</strong><p>{{ $result['closed_at'] ? 'Ditutup pada '.$result['closed_at']->locale('id')->translatedFormat('d F Y').'.' : 'Perubahan jadwal akan diinformasikan oleh pengelola.' }}</p></section>
            <section class="neo-card journey-info"><h2>Update terakhir</h2><p class="public-latest-time">{{ data_get($result['events']->first(), 'date')?->locale('id')->translatedFormat('d F Y') ?? 'Belum ada update' }}</p><p class="journey-info-note">Ada pertanyaan tentang proses atau jadwal pesananmu?</p><a class="button button-neutral" href="{{ route('home').'#kontak' }}">Hubungi pengelola <x-icon name="arrow-up-right" size="17" /></a></section>
        </aside>
    </div>
</div>
