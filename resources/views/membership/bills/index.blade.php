@extends('layouts.portal')
@section('title', 'Bill saya')
@section('description', 'Lihat rincian tagihan dan status pembayaran layananmu.')
@section('content')
    <section class="neo-card"><form class="filter-bar" method="get"><label for="status" class="sr-only">Status pembayaran</label><select name="status" id="status"><option value="">Semua pembayaran</option>@foreach(\App\Models\Bill::STATUS_LABELS as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select><button class="button button-neutral">Filter</button></form>
        <div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>BILL</th><th>TOTAL</th><th>STATUS</th><th>JATUH TEMPO</th><th>DETAIL</th></tr></thead><tbody>
            @forelse($bills as $bill)
                <tr><td><strong>{{ $bill->title }}</strong><small>{{ $bill->reference }}</small></td><td class="numeric">Rp{{ number_format($bill->total, 0, ',', '.') }}</td><td><span class="badge" style="background:{{ $bill->status === 'paid' ? '#ddfbe9' : ($bill->status === 'cancelled' ? '#ffe0e0' : '#fff3bd') }}">{{ \App\Models\Bill::STATUS_LABELS[$bill->status] }}</span></td><td>{{ $bill->due_date?->format('d M Y') ?? '—' }}</td><td><a class="row-action" href="{{ route('membership.bills.show', $bill) }}">Lihat bill</a></td></tr>
            @empty
                <tr><td colspan="5" class="portal-empty">Belum ada bill. Tagihan layananmu akan muncul di sini.</td></tr>
            @endforelse
        </tbody></table></div><x-pager :paginator="$bills" />
    </section>
@endsection
