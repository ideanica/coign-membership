@extends('layouts.portal')
@section('title', 'Detail pesanan')
@section('description', $tracking->reference)
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/bill.css') }}?v={{ filemtime(public_path('css/bill.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/order-detail.css') }}?v={{ filemtime(public_path('css/order-detail.css')) }}">
@endpush
@push('scripts')
    <script src="{{ asset('js/tracking.js') }}" defer></script>
    <script src="{{ asset('js/order-detail.js') }}" defer></script>
@endpush
@section('actions')
    <a class="button button-neutral" href="{{ route('admin.tracking.index') }}"><x-icon name="chevron-left" size="16" /> Semua pesanan</a>
    @if($tracking->bill)<button class="button button-neutral" type="button" data-print><x-icon name="download" size="16" /> Cetak bill</button>@endif
@endsection
@section('content')
<div class="order-detail-content">
    <div class="order-detail-grid">
        <section class="neo-card order-summary" aria-labelledby="order-title">
            <div class="order-card-heading"><span class="section-kicker">INFORMASI PESANAN</span><span class="badge status-badge" style="--status-color:{{ $tracking->status->color }}">{{ $tracking->status->label }}</span></div>
            <h2 id="order-title">{{ $tracking->title }}</h2>
            @if(filled($tracking->description))<p class="order-description">{{ $tracking->description }}</p>@endif
            <dl class="order-meta">
                <div><dt>Pelanggan</dt><dd>{{ $tracking->user?->name ?? $tracking->bill?->customer_name ?? 'Pelanggan tanpa akun' }}<span>{{ $tracking->user?->email ?? $tracking->bill?->customer_email }}</span></dd></div>
                <div><dt>Nomor member</dt><dd>{{ $tracking->user?->member_number ?? 'Tanpa akun member' }}</dd></div>
                <div><dt>Tanggal pesanan</dt><dd>{{ $tracking->created_at->locale('id')->translatedFormat('d F Y') }}</dd></div>
                <div><dt>Target selesai</dt><dd>{{ $tracking->due_date?->locale('id')->translatedFormat('d F Y') ?? 'Belum ditentukan' }}</dd></div>
            </dl>
            <div class="order-card-actions">
                <button class="button" type="button" data-order-open="order-edit-dialog"><x-icon name="calendar" size="16" /> Kelola tracking</button>
                <button class="button button-neutral" type="button" data-order-open="order-access-dialog"><x-icon name="info" size="16" /> Kode tracking</button>
            </div>
        </section>
        <section class="neo-card order-payment" aria-labelledby="order-payment-title">
            <div class="order-card-heading"><h2 id="order-payment-title">Pembayaran</h2>@if($tracking->bill)<span class="badge order-payment-status status-{{ $tracking->bill->status }}">{{ \App\Models\Bill::STATUS_LABELS[$tracking->bill->status] }}</span>@endif</div>
            @if($tracking->bill)
                <p class="order-total-label">Total tagihan</p><p class="order-total">Rp{{ number_format($tracking->bill->total, 0, ',', '.') }}</p>
                <dl class="order-payment-meta">
                    <div><dt>{{ $tracking->bill->quantity }} × Rp{{ number_format($tracking->bill->unit_price, 0, ',', '.') }}</dt><dd>Rp{{ number_format($tracking->bill->quantity * $tracking->bill->unit_price, 0, ',', '.') }}</dd></div>
                    @if($tracking->bill->discount)<div><dt>Diskon</dt><dd>−Rp{{ number_format($tracking->bill->discount, 0, ',', '.') }}</dd></div>@endif
                    <div><dt>Jatuh tempo</dt><dd>{{ $tracking->bill->due_date?->locale('id')->translatedFormat('d M Y') ?? 'Belum ditentukan' }}</dd></div>
                    @if($tracking->bill->status === 'paid')
                        <div><dt>Metode</dt><dd>{{ $tracking->bill->payment }}</dd></div>
                        <div><dt>Dibayar pada</dt><dd>{{ $tracking->bill->paid_at?->locale('id')->translatedFormat('d M Y, H:i') }}</dd></div>
                    @endif
                </dl>
                <p class="order-bill-reference">{{ $tracking->bill->reference }}</p>
                @if($tracking->bill->status === 'unpaid')
                    <div class="order-payment-actions">
                        <button class="button" type="button" data-order-open="order-payment-dialog"><x-icon name="check" size="16" /> Tandai lunas</button>
                        <form method="post" action="{{ route('admin.bills.cancel', $tracking->bill) }}" data-confirm="Batalkan bill untuk pesanan ini? Bill yang dibatalkan tidak dapat dibayar.">@csrf<button class="row-action danger" type="submit">Batalkan bill</button></form>
                    </div>
                @endif
            @else
                <p class="order-description">Pesanan ini belum memiliki bill.</p>
            @endif
        </section>
        <section class="neo-card order-history" aria-labelledby="order-history-title">
            <div class="order-card-heading"><div><span class="section-kicker">PERKEMBANGAN</span><h2 id="order-history-title">Riwayat pesanan</h2></div>@if($tracking->events->count() > 3)<button class="text-button" type="button" data-order-open="order-history-dialog">Lihat semua ({{ $tracking->events->count() }}) <x-icon name="arrow-up-right" size="16" /></button>@endif</div>
            @include('shared.timeline', ['events' => $tracking->events->take(3)])
        </section>
    </div>
    @if($tracking->bill)
        <section id="bill" class="order-print-only" aria-label="Dokumen bill untuk dicetak">
            @include('shared.bill-content', ['bill' => $tracking->bill, 'embedded' => true, 'showControls' => false])
        </section>
    @endif
</div>
@include('admin.tracking.dialogs')
@endsection
