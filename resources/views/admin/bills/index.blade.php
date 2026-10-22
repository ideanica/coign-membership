@extends('layouts.portal')
@section('title', 'Bill')
@section('description', 'Kelola tagihan dan konfirmasi pembayaran untuk member.')
@section('actions')<a class="button" href="{{ route('admin.tracking.create') }}">Buat pesanan <x-icon name="receipt" size="16" /></a>@endsection
@section('content')
    <section class="neo-card"><form class="filter-bar" method="get"><x-admin-search label="Cari bill" placeholder="Cari judul, nomor bill, atau member" /><label for="status" class="sr-only">Status pembayaran</label><select name="status" id="status"><option value="">Semua pembayaran</option>@foreach(\App\Models\Bill::STATUS_LABELS as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></form>
        <div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>BILL</th><th>MEMBER</th><th>TOTAL</th><th>STATUS</th><th>JATUH TEMPO</th><th>AKSI</th></tr></thead><tbody>
            @forelse($bills as $bill)
                <tr><td><strong>{{ $bill->title }}</strong><small>{{ $bill->reference }}</small></td><td>{{ $bill->customer_name ?? $bill->user?->name ?? 'Pelanggan tanpa akun' }}</td><td class="numeric">Rp{{ number_format($bill->total, 0, ',', '.') }}</td><td><span class="badge" style="background:{{ $bill->status === 'paid' ? '#ddfbe9' : ($bill->status === 'cancelled' ? '#ffe0e0' : '#fff3bd') }}">{{ \App\Models\Bill::STATUS_LABELS[$bill->status] }}</span></td><td>{{ $bill->due_date?->format('d M Y') ?? '—' }}</td><td><a class="row-action" href="{{ route('admin.bills.show', $bill) }}">Lihat bill</a></td></tr>
            @empty
                <tr><td colspan="6" class="portal-empty">Belum ada bill yang cocok.</td></tr>
            @endforelse
        </tbody></table></div><x-pager :paginator="$bills" />
    </section>
@endsection
