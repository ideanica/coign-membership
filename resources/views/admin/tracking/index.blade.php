@extends('layouts.portal')
@section('title', 'Pesanan')
@section('description', 'Kelola pesanan per pelanggan. Rincian bill dan pembayaran tersedia di dalam detail pesanan.')
@section('actions')<a class="button" href="{{ route('admin.tracking.create') }}">Buat pesanan <x-icon name="calendar" size="16" /></a>@endsection
@section('content')
    <section class="neo-card"><form class="filter-bar" method="get">
        <label class="sr-only" for="q">Cari pesanan</label><input name="q" id="q" value="{{ request('q') }}" placeholder="Cari judul, nomor pesanan, atau pelanggan…">
        <label class="sr-only" for="status">Status pesanan</label><select id="status" name="status"><option value="">Semua status pesanan</option>@foreach($statuses as $status)<option value="{{ $status->id }}" @selected(request('status') == $status->id)>{{ $status->label }}</option>@endforeach</select>
        <label class="sr-only" for="payment_status">Status pembayaran</label><select id="payment_status" name="payment_status"><option value="">Semua pembayaran</option>@foreach(\App\Models\Bill::STATUS_LABELS as $value => $label)<option value="{{ $value }}" @selected(request('payment_status') === $value)>{{ $label }}</option>@endforeach</select>
        <button class="button button-neutral">Filter</button>
    </form>
        <div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>PESANAN</th><th>PELANGGAN</th><th>TOTAL / PEMBAYARAN</th><th>STATUS</th><th>TARGET</th><th>AKSI</th></tr></thead><tbody>
            @forelse($trackings as $tracking)
                <tr><td><strong>{{ $tracking->title }}</strong><small>{{ $tracking->reference }}</small></td><td>{{ $tracking->user?->name ?? $tracking->bill?->customer_name ?? 'Pelanggan tanpa akun' }}</td><td>@if($tracking->bill)<strong>Rp{{ number_format($tracking->bill->total, 0, ',', '.') }}</strong><small>{{ \App\Models\Bill::STATUS_LABELS[$tracking->bill->status] }}</small>@else Belum ada bill @endif</td><td><span class="badge status-badge" style="--status-color:{{ $tracking->status->color }}">{{ $tracking->status->label }}</span></td><td>{{ $tracking->due_date?->format('d M Y') ?? '—' }}</td><td><a class="row-action" href="{{ route('admin.tracking.show', $tracking) }}">Detail pesanan</a></td></tr>
            @empty
                <tr><td colspan="6" class="portal-empty">Belum ada pesanan yang cocok.</td></tr>
            @endforelse
        </tbody></table></div><x-pager :paginator="$trackings" />
    </section>
@endsection
