@extends('layouts.portal')
@section('title', __('Bill saya'))
@section('description', __('Lihat rincian tagihan dan status pembayaran layananmu.'))
@section('content')
    <section class="neo-card"><form class="filter-bar" method="get"><label for="status" class="sr-only">{{ __('Status pembayaran') }}</label><select name="status" id="status"><option value="">{{ __('Semua pembayaran') }}</option>@foreach(\App\Models\Bill::STATUS_LABELS as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ __($label) }}</option>@endforeach</select><button class="button button-neutral">{{ __('Filter') }}</button></form>
        <div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>BILL</th><th>TOTAL</th><th>STATUS</th><th>{{ __('JATUH TEMPO') }}</th><th>{{ __('DETAIL') }}</th></tr></thead><tbody>
            @forelse($bills as $bill)
                <tr><td><strong>{{ $bill->title }}</strong><small>{{ $bill->reference }}</small></td><td class="numeric">Rp{{ number_format($bill->total, 0, ',', '.') }}</td><td><span class="badge" style="background:{{ $bill->status === 'paid' ? '#ddfbe9' : ($bill->status === 'cancelled' ? '#ffe0e0' : '#fff3bd') }}">{{ __(\App\Models\Bill::STATUS_LABELS[$bill->status]) }}</span></td><td>{{ $bill->due_date?->format('d M Y') ?? '—' }}</td><td><a class="row-action" href="{{ route('membership.bills.show', $bill) }}">{{ __('Lihat bill') }}</a></td></tr>
            @empty
                <tr><td colspan="5" class="portal-empty">{{ __('Belum ada bill. Tagihan layananmu akan muncul di sini.') }}</td></tr>
            @endforelse
        </tbody></table></div><x-pager :paginator="$bills" />
    </section>
@endsection
