@extends('layouts.portal')
@section('title', 'Pesanan saya')
@section('description', 'Pantau perkembangan dan bill untuk pesanan milikmu di sini.')
@push('styles')<link rel="stylesheet" href="{{ asset('css/tracking.css') }}?v={{ filemtime(public_path('css/tracking.css')) }}">@endpush
@section('content')
<div class="tracking-page">
    <section class="neo-card tracking-overview" aria-label="Ringkasan tracking">
        <div class="tracking-overview-copy"><span class="tracking-kicker"><span class="live-dot"></span> YOUR SERVICE JOURNEY</span><h2>Dari mulai, <br>sampai beres<span>✦</span></h2><p>Semua perkembangan layananmu, dalam satu tempat.</p></div>
        <div class="tracking-summary">
            <div><x-icon name="calendar" size="22" /><strong>{{ $summary['total'] }}</strong><span>Total layanan</span></div>
            <div class="summary-active"><x-icon name="spark" size="22" /><strong>{{ $summary['active'] }}</strong><span>Masih berjalan</span></div>
            <div class="summary-closed"><x-icon name="check" size="22" /><strong>{{ $summary['closed'] }}</strong><span>Tracking ditutup</span></div>
        </div>
    </section>
    <div class="tracking-section-heading"><h2>Pesanan kamu <span class="badge">{{ $trackings->total() }}</span></h2><span>Update langsung dari tim pengelola</span></div>
    <section class="neo-card tracking-filter"><form method="get" class="tracking-filter-form">
        <div class="tracking-search"><x-icon name="search" size="20" /><label class="sr-only" for="q">Cari tracking</label><input id="q" name="q" value="{{ request('q') }}" placeholder="Cari layanan atau nomor tracking…"></div>
        <label class="sr-only" for="status">Status tracking</label><select name="status" id="status"><option value="">Semua status</option>@foreach($statuses as $status)<option value="{{ $status->id }}" @selected(request('status') == $status->id)>{{ $status->label }}</option>@endforeach</select>
        <button class="button" type="submit">Tampilkan <x-icon name="arrow-right" size="16" /></button>
        @if(request()->filled('q') || request()->filled('status'))<a class="small-link" href="{{ route('membership.tracking.index') }}">Reset</a>@endif
    </form></section>
    <div class="tracking-grid journey-grid">
        @forelse($trackings as $tracking)
        <article class="neo-card journey-card">
            <div class="journey-card-top"><span class="journey-icon" style="--status-color:{{ $tracking->status->color }}"><x-icon name="gift" size="24" /></span><span class="badge status-badge" style="--status-color:{{ $tracking->status->color }}">{{ $tracking->status->label }}</span></div>
            <p class="tracking-ref">{{ $tracking->reference }}</p><h3>{{ $tracking->title }}</h3><p class="journey-service">{{ $tracking->service?->name ?? 'Layanan khusus' }}</p>
            <div class="journey-update"><span class="tracking-kicker">KABAR TERAKHIR</span><p>{{ filled($tracking->latestEvent?->note) ? $tracking->latestEvent->note : ($tracking->latestEvent?->status_label ?? 'Menunggu update pertama dari tim.') }}</p>@if($tracking->latestEvent)<time datetime="{{ $tracking->latestEvent->created_at->toIso8601String() }}">{{ $tracking->latestEvent->created_at->locale('id')->translatedFormat('d M Y · H:i') }}</time>@endif</div>
            <div class="journey-card-meta"><span><x-icon name="calendar" size="16" /><span>Target selesai<strong>{{ $tracking->due_date?->locale('id')->translatedFormat('d M Y') ?? 'Belum ditentukan' }}</strong></span></span><span class="journey-update-count">{{ $tracking->events_count }} update</span></div>
            <a class="button journey-cta" href="{{ route('membership.tracking.show', $tracking) }}">Detail pesanan & bill <x-icon name="arrow-up-right" size="19" /></a>
        </article>
        @empty
        <section class="neo-card journey-empty"><span class="journey-icon"><x-icon name="search" size="30" /></span><h2>{{ request()->filled('q') || request()->filled('status') ? 'Tracking belum ditemukan' : 'Perjalananmu dimulai di sini' }}</h2><p>{{ request()->filled('q') || request()->filled('status') ? 'Coba kata kunci lain atau tampilkan semua status layanan.' : 'Layanan yang dibuat pengelola akan muncul di sini, lengkap dengan setiap perkembangannya.' }}</p>@if(request()->filled('q') || request()->filled('status'))<a class="button button-neutral" href="{{ route('membership.tracking.index') }}">Lihat semua tracking</a>@endif</section>
        @endforelse
    </div>
    <div class="journey-pagination"><x-pager :paginator="$trackings" /></div>
</div>
@endsection
