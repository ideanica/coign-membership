@extends('layouts.portal')
@section('title', 'Ringkasan')
@section('description', 'Pantau member, pekerjaan yang berjalan, dan pembayaran terbaru.')
@section('actions')<a href="{{ route('admin.tracking.create') }}" class="button">Buat pesanan <x-icon name="arrow-right" size="16" /></a>@endsection
@section('content')
    <div class="stat-grid">
        <div class="neo-card dashboard-stat"><p>Total member</p><strong>{{ number_format($stats['members'], 0, ',', '.') }}</strong></div>
        <div class="neo-card dashboard-stat"><p>Pesanan berjalan</p><strong>{{ number_format($stats['active_tracking'], 0, ',', '.') }}</strong></div>
        <div class="neo-card dashboard-stat"><p>Bill belum lunas</p><strong>{{ number_format($stats['unpaid_bills'], 0, ',', '.') }}</strong></div>
        <div class="neo-card dashboard-stat"><p>Pembayaran diterima</p><strong>Rp{{ number_format($stats['revenue'], 0, ',', '.') }}</strong></div>
    </div>
    <div class="portal-grid">
        <section class="neo-card portal-card"><div class="card-heading"><h2>Pesanan terbaru</h2><a href="{{ route('admin.tracking.index') }}" class="small-link">Lihat semua</a></div>
            @forelse($trackings as $tracking)
                <a class="list-row" href="{{ route('admin.tracking.show', $tracking) }}"><div><strong>{{ $tracking->title }}</strong><small>{{ $tracking->user?->name ?? 'Pelanggan tanpa akun' }} · {{ $tracking->reference }}</small></div><span class="badge status-badge" style="--status-color:{{ $tracking->status->color }}">{{ $tracking->status->label }}</span></a>
            @empty
                <p class="portal-empty">Belum ada tracking. Buat tracking pertama untuk member.</p>
            @endforelse
        </section>
        <section class="neo-card portal-card"><div class="card-heading"><h2>Menunggu pembayaran</h2><a href="{{ route('admin.tracking.index', ['payment_status' => 'unpaid']) }}" class="small-link">Lihat pesanan</a></div>
            @forelse($bills as $bill)
                <a class="list-row" href="{{ $bill->member_tracking_id ? route('admin.tracking.show', $bill->member_tracking_id).'#bill' : route('admin.bills.show', $bill) }}"><div><strong>{{ $bill->title }}</strong><small>{{ $bill->customer_name ?? $bill->user?->name ?? 'Pelanggan tanpa akun' }}</small></div><strong>Rp{{ number_format($bill->total, 0, ',', '.') }}</strong></a>
            @empty
                <p class="portal-empty">Tidak ada bill yang menunggu pembayaran.</p>
            @endforelse
        </section>
    </div>
    <div class="form-help" style="margin-top:24px">Buat pesanan dari menu <a class="small-link" href="{{ route('admin.tracking.index') }}">Pesanan</a>. Isi pelanggan, pekerjaan, dan harga secara manual; bill menjadi bagian dari detail pesanan.</div>
@endsection
