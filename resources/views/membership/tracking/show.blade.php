@extends('layouts.portal')
@section('title', __('Detail pesanan'))
@section('description', __('Dari permintaan pertama hingga update terbaru, semuanya tercatat.'))
@section('actions')<a class="button button-neutral" href="{{ route('membership.tracking.index') }}"><x-icon name="chevron-left" size="16" /> {{ __('Semua pesanan') }}</a>@endsection
@push('styles')<link rel="stylesheet" href="{{ asset('css/tracking.css') }}?v={{ filemtime(public_path('css/tracking.css')) }}">@endpush
@push('styles')<link rel="stylesheet" href="{{ asset('css/bill.css') }}?v={{ filemtime(public_path('css/bill.css')) }}">@endpush
@push('scripts')<script src="{{ asset('js/tracking.js') }}?v={{ filemtime(public_path('js/tracking.js')) }}" defer></script>@endpush
@push('styles')<link rel="stylesheet" href="{{ asset('css/member-order-detail.css') }}?v={{ filemtime(public_path('css/member-order-detail.css')) }}">@endpush
@push('scripts')<script src="{{ asset('js/order-detail.js') }}?v={{ filemtime(public_path('js/order-detail.js')) }}" defer></script>@endpush
@section('content')
<div class="tracking-page order-detail-content">
    <section class="neo-card journey-hero">
        <div class="journey-hero-main"><div class="journey-reference"><span class="tracking-kicker">{{ $tracking->public_enabled ? $tracking->public_code : $tracking->reference }}</span><button type="button" class="reference-copy" data-copy-reference="{{ $tracking->public_enabled ? $tracking->public_code : $tracking->reference }}" aria-label="{{ __('Salin nomor tracking') }}">{{ __('Salin') }}</button><span class="sr-only" data-copy-status role="status"></span></div><h2>{{ $tracking->title }}</h2><p><x-icon name="gift" size="18" /> {{ $tracking->service?->name ?? __('Layanan khusus') }}</p></div>
        <div class="journey-hero-status"><span class="tracking-kicker">{{ __('STATUS SAAT INI') }}</span><span class="badge status-badge" style="--status-color:{{ $tracking->status->color }}">{{ __($tracking->status->label) }}</span><p>{{ $tracking->closed_at ? __('Tracking ini sudah ditutup.') : __('Layananmu sedang dalam perjalanan.') }}</p></div>
    </section>
    <p class="journey-info-note">{{ $tracking->public_enabled ? __('Gunakan kode ORD di atas untuk melacak pesanan di halaman Tracking tanpa login.') : __('Tracking publik belum aktif. Pesanan ini hanya dapat dilihat melalui akunmu. Hubungi pengelola jika membutuhkan kode tracking publik.') }}</p>
    <div class="journey-detail-grid">
        <section class="neo-card journey-timeline-panel"><div class="journey-panel-heading"><span class="journey-icon"><x-icon name="spark" size="22" /></span><div><h2>{{ __('Setiap langkah, tercatat.') }}</h2><p>{{ __('Update terbaru tampil paling atas.') }}</p></div><span class="badge">{{ $tracking->events->count() }} update</span></div>
            @include('membership.tracking.timeline', ['events' => $tracking->events->take(3)])
            @if($tracking->events->count() > 3)<button type="button" class="button button-neutral member-history-button" data-dialog-open="member-order-history">{{ __('Lihat semua') }} ({{ $tracking->events->count() }}) <x-icon name="arrow-up-right" size="16" /></button>@endif
            <div class="journey-timeline-foot"><x-icon name="info" size="16" /><span>{{ __('Riwayat menampilkan status layanan pada saat setiap update dibuat.') }}</span></div>
        </section>
        <aside class="journey-sidebar">
            <section class="neo-card journey-target"><span class="tracking-kicker"><x-icon name="calendar" size="17" /> {{ __('TARGET SELESAI') }}</span><strong>{{ $tracking->due_date?->locale(app()->getLocale())->translatedFormat('d F Y') ?? __('Belum ditentukan') }}</strong><p>{{ $tracking->closed_at ? __('Ditutup pada').' '.$tracking->closed_at->locale(app()->getLocale())->translatedFormat('d F Y').'.' : __('Tanggal target layanan. Perubahan jadwal akan diinformasikan oleh tim.') }}</p></section>
            <section class="neo-card journey-bill member-payment-card">
                <div class="journey-panel-heading"><x-icon name="receipt" size="24" /><h2>{{ __('Bill pesanan') }}</h2></div>
                @if($tracking->bill)
                    <span class="badge status-{{ $tracking->bill->status }}">{{ __(\App\Models\Bill::STATUS_LABELS[$tracking->bill->status]) }}</span>
                    <span class="member-total-label">{{ __('Total tagihan') }}</span>
                    <strong class="member-order-total">Rp{{ number_format($tracking->bill->total, 0, ',', '.') }}</strong>
                    <dl class="member-payment-meta">
                        <div><dt>{{ __('Nomor bill') }}</dt><dd>{{ $tracking->bill->reference }}</dd></div>
                        <div><dt>{{ __('Jatuh tempo') }}</dt><dd>{{ $tracking->bill->due_date?->locale(app()->getLocale())->translatedFormat('d M Y') ?? '-' }}</dd></div>
                        @if($tracking->bill->status === 'paid')<div><dt>{{ __('Pembayaran diterima') }}</dt><dd>{{ $tracking->bill->paid_at?->locale(app()->getLocale())->translatedFormat('d M Y') ?? '-' }}</dd></div>@endif
                    </dl>
                    <button class="button button-neutral" type="button" data-print>{{ __('Cetak / simpan PDF') }} <x-icon name="download" size="16" /></button>
                    @if($tracking->bill->status === 'unpaid')<p class="member-payment-note">{{ __('Hubungi pengelola untuk detail pembayaran. Simpan nomor bill sebagai referensi konfirmasi.') }}</p>@endif
                @else
                    <p class="member-payment-note">{{ __('Pesanan ini belum memiliki bill.') }}</p>
                @endif
            </section>
            <button type="button" class="button button-neutral member-details-button" data-dialog-open="member-order-info">{{ __('Detail layanan') }} <x-icon name="arrow-up-right" size="16" /></button>
            @if(filled($settings['support_email'] ?? ''))<a class="journey-support" href="mailto:{{ $settings['support_email'] }}"><span><strong>{{ __('Butuh bantuan?') }}</strong><span>{{ __('Tanyakan perkembangan layananmu.') }}</span></span><x-icon name="arrow-up-right" size="23" /></a>@endif
        </aside>
    </div>
    @if($tracking->bill)
        <section id="bill" class="member-order-print" aria-label="{{ __('Bill pesanan') }}">
            @include('shared.bill-content', ['bill' => $tracking->bill, 'embedded' => true])
        </section>
    @endif
</div>
<dialog id="member-order-info" class="neo-dialog member-order-dialog" data-managed-dialog aria-labelledby="member-info-title">
    <div class="dialog-header"><h2 id="member-info-title">{{ __('Detail layanan') }}</h2><button type="button" class="dialog-close" data-dialog-close aria-label="{{ __('Tutup') }}"><x-icon name="close" size="20" /></button></div>
            <section class="journey-info"><dl><div><dt>{{ __('Mulai dibuat') }}</dt><dd>{{ $tracking->created_at->locale(app()->getLocale())->translatedFormat('d F Y') }}</dd></div><div><dt>{{ __('Update terakhir') }}</dt><dd>{{ $tracking->events->first()?->created_at->locale(app()->getLocale())->translatedFormat('d M Y · H:i') ?? __('Belum ada update') }}</dd></div></dl>@if(filled($tracking->description))<div class="journey-description"><span class="tracking-kicker">{{ __('CATATAN LAYANAN') }}</span><p>{{ $tracking->description }}</p></div>@endif</section>

</dialog>
<dialog id="member-order-history" class="neo-dialog member-order-dialog member-history-dialog" data-managed-dialog aria-labelledby="member-history-title">
    <div class="dialog-header"><h2 id="member-history-title">{{ __('Riwayat pesanan') }}</h2><button type="button" class="dialog-close" data-dialog-close aria-label="{{ __('Tutup') }}"><x-icon name="close" size="20" /></button></div>
    @include('membership.tracking.timeline', ['events' => $tracking->events])
</dialog>
@endsection
