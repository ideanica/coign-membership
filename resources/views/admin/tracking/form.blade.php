@extends('layouts.portal')
@section('title', $tracking->exists ? 'Kelola pesanan' : 'Buat pesanan')
@section('description', 'Isi pesanan dan harga secara manual. Pesanan hanya tampil pada akun pelanggan yang dipilih.')
@push('styles')<link rel="stylesheet" href="{{ asset('css/bill.css') }}">@endpush
@push('scripts')<script src="{{ asset('js/tracking.js') }}?v={{ filemtime(public_path('js/tracking.js')) }}" defer></script>@endpush
@section('content')
    <div class="portal-grid">
        @include('admin.tracking.order-form')
        @if($tracking->exists)
            <section class="neo-card portal-card"><h2>Timeline tracking</h2><p class="muted">Riwayat update yang terlihat oleh member.</p>@include('shared.timeline', ['events' => $tracking->events])</section>
        @else
            <section class="neo-card portal-card"><h2>Pesanan untuk pelanggan</h2><p class="muted">Pilih akun pelanggan agar pesanan muncul di halaman member miliknya. Isi judul, deskripsi, harga, dan jadwal secara manual. Bill tersedia di dalam detail pesanan setelah disimpan.</p></section>
        @endif
    </div>
@endsection
