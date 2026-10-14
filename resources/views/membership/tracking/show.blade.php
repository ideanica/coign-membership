@extends('layouts.portal')
@section('title', 'Perjalanan layanan')
@section('description', 'Dari permintaan pertama hingga update terbaru, semuanya tercatat.')
@section('actions')<a class="button button-neutral" href="{{ route('membership.tracking.index') }}"><x-icon name="chevron-left" size="16" /> Semua tracking</a>@endsection
@push('styles')<link rel="stylesheet" href="{{ asset('css/tracking.css') }}?v={{ filemtime(public_path('css/tracking.css')) }}">@endpush
@push('scripts')<script src="{{ asset('js/tracking.js') }}" defer></script>@endpush
@section('content')
<div class="tracking-page">
    <section class="neo-card journey-hero">
        <div class="journey-hero-main"><div class="journey-reference"><span class="tracking-kicker">{{ $tracking->reference }}</span><button type="button" class="reference-copy" data-copy-reference="{{ $tracking->reference }}" aria-label="Salin nomor tracking">Salin</button><span class="sr-only" data-copy-status role="status"></span></div><h2>{{ $tracking->title }}</h2><p><x-icon name="gift" size="18" /> {{ $tracking->service?->name ?? 'Layanan khusus' }}</p></div>
        <div class="journey-hero-status"><span class="tracking-kicker">STATUS SAAT INI</span><span class="badge status-badge" style="--status-color:{{ $tracking->status->color }}">{{ $tracking->status->label }}</span><p>{{ $tracking->closed_at ? 'Tracking ini sudah ditutup.' : 'Layananmu sedang dalam perjalanan.' }}</p></div>
    </section>
    <div class="journey-detail-grid">
        <section class="neo-card journey-timeline-panel"><div class="journey-panel-heading"><span class="journey-icon"><x-icon name="spark" size="22" /></span><div><h2>Setiap langkah, tercatat.</h2><p>Update terbaru tampil paling atas.</p></div><span class="badge">{{ $tracking->events->count() }} update</span></div>
            <ol class="journey-timeline">
                @forelse($tracking->events as $event)
                <li class="{{ $loop->first ? 'is-latest' : '' }}"><span class="journey-step" style="--status-color:{{ $event->status_color }}">{{ $tracking->events->count() - $loop->index }}</span><div class="journey-event"><div class="journey-event-date"><time datetime="{{ $event->created_at->toIso8601String() }}">{{ $event->created_at->locale('id')->translatedFormat('d F Y') }} <span>{{ $event->created_at->format('H:i') }}</span></time>@if($loop->first)<span class="latest-label">TERBARU <x-icon name="spark" size="12" /></span>@endif</div><span class="badge status-badge" style="--status-color:{{ $event->status_color }}">{{ $event->status_label }}</span><p>{{ filled($event->note) ? $event->note : 'Status layanan diperbarui oleh tim pengelola.' }}</p></div></li>
                @empty
                <li><span class="journey-step"><x-icon name="calendar" size="18" /></span><div class="journey-event"><h3>Menunggu kabar pertama</h3><p>Update dari tim akan muncul di sini setelah layanan diproses.</p></div></li>
                @endforelse
            </ol>
            <div class="journey-timeline-foot"><x-icon name="info" size="16" /><span>Riwayat menampilkan status layanan pada saat setiap update dibuat.</span></div>
        </section>
        <aside class="journey-sidebar">
            <section class="neo-card journey-target"><span class="tracking-kicker"><x-icon name="calendar" size="17" /> TARGET SELESAI</span><strong>{{ $tracking->due_date?->locale('id')->translatedFormat('d F Y') ?? 'Belum ditentukan' }}</strong><p>{{ $tracking->closed_at ? 'Ditutup pada '.$tracking->closed_at->locale('id')->translatedFormat('d F Y').'.' : 'Tanggal target layanan. Perubahan jadwal akan diinformasikan oleh tim.' }}</p></section>
            <section class="neo-card journey-info"><h2>Detail layanan</h2><dl><div><dt>Mulai dibuat</dt><dd>{{ $tracking->created_at->locale('id')->translatedFormat('d F Y') }}</dd></div><div><dt>Update terakhir</dt><dd>{{ $tracking->events->first()?->created_at->locale('id')->translatedFormat('d M Y · H:i') ?? 'Belum ada update' }}</dd></div></dl>@if(filled($tracking->description))<div class="journey-description"><span class="tracking-kicker">CATATAN LAYANAN</span><p>{{ $tracking->description }}</p></div>@endif</section>
            @if($tracking->bill)<section class="neo-card journey-bill"><div class="journey-panel-heading"><x-icon name="receipt" size="24" /><h2>Bill layanan</h2></div><span class="badge">{{ \App\Models\Bill::STATUS_LABELS[$tracking->bill->status] }}</span><p>Rincian tagihan untuk layanan ini tersedia di halaman bill.</p><a class="button button-neutral" href="{{ route('membership.bills.show', $tracking->bill) }}">Buka bill <x-icon name="arrow-up-right" size="17" /></a></section>@endif
            @if(filled($settings['support_email'] ?? ''))<a class="journey-support" href="mailto:{{ $settings['support_email'] }}"><span><strong>Butuh bantuan?</strong><span>Tanyakan perkembangan layananmu.</span></span><x-icon name="arrow-up-right" size="23" /></a>@endif
        </aside>
    </div>
</div>
@endsection
