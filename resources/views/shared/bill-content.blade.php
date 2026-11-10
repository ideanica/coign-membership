@php
    $settings = $settings ?? [];
    $admin = request()->routeIs('admin.*');
    $customerName = $bill->customer_name ?? $bill->user?->name ?? __('Pelanggan tanpa akun');
    $customerEmail = $bill->customer_email ?? $bill->user?->email;
    $customerNumber = $bill->customer_number ?? $bill->user?->member_number;
    $issuer = $bill->issuer_name ?? $settings['brand_name'] ?? 'Coignitive';
    $email = $bill->issuer_email ?? $settings['support_email'] ?? '';
    $hasDeposit = $bill->dp_amount > 0;
    $showDeposit = $hasDeposit && $bill->status !== 'cancelled';
    $depositPercentageLabel = $bill->downPaymentPercentageLabel();
@endphp
<div class="bill-document">
    <p class="invoice-screen-note"><x-icon name="receipt" size="15" /> {{ __('Siap cetak Letter &middot; Rincian invoice terkunci setelah diterbitkan.') }}</p>
    <article class="neo-card invoice-card invoice-sheet">
        <header class="invoice-document-header">
            <div><div class="invoice-document-logo"><x-brand-logo full :settings="['brand_name' => $issuer]" /></div><strong class="invoice-issuer">{{ $issuer }}</strong><p>{{ filled($email) ? $email : '-' }}</p></div>
            <div class="invoice-document-id"><p class="invoice-kicker">{{ __('INVOICE') }}</p><h2>{{ __('Untuk ide') }}<br>{{ __('& momenmu.') }}</h2><p class="invoice-reference">{{ $bill->reference }}</p><span class="badge invoice-status status-{{ $bill->statusTone() }}">{{ __($bill->statusLabel()) }}</span></div>
        </header>
        <div class="invoice-document-meta">
            <section><h3>{{ __('Ditagihkan kepada') }}</h3><strong>{{ $customerName }}</strong><p>{{ filled($customerEmail) ? $customerEmail : '-' }}</p><p>Member: {{ filled($customerNumber) ? $customerNumber : '-' }}</p></section>
            <section><h3>{{ __('Informasi tagihan') }}</h3><dl><div><dt>{{ __('Diterbitkan') }}</dt><dd>{{ $bill->created_at->locale(app()->getLocale())->translatedFormat('d F Y') }}</dd></div><div><dt>{{ __('Jatuh tempo') }}</dt><dd>{{ $bill->due_date?->locale(app()->getLocale())->translatedFormat('d F Y') ?? '-' }}</dd></div><div><dt>{{ __('Pesanan') }}</dt><dd>{{ $bill->tracking?->reference ?? '-' }}</dd></div><div><dt>{{ __('Skema pembayaran') }}</dt><dd>{{ $showDeposit ? __('DP').' '.$depositPercentageLabel.'%' : __('Lunas') }}</dd></div></dl></section>
        </div>
        <p class="invoice-order-title">{{ $bill->title }}</p>
        <div class="invoice-items"><div class="invoice-items-heading"><span>{{ __('Layanan / pekerjaan') }}</span><span>Subtotal</span></div>
            @foreach($bill->lineItems() as $item)
                <div class="invoice-item"><div><h3>{{ $item['name'] }}</h3><p>{{ number_format($item['quantity'], 0, ',', '.') }} &times; Rp{{ number_format($item['unit_price'], 0, ',', '.') }}</p></div><strong>Rp{{ number_format($item['quantity'] * $item['unit_price'], 0, ',', '.') }}</strong></div>
            @endforeach
        </div>
        <div class="invoice-document-totals">
            <div><span>Subtotal</span><strong>Rp{{ number_format($bill->subtotal(), 0, ',', '.') }}</strong></div>
            @if($bill->discount > 0)<div><span>{{ __('Diskon') }}</span><strong>&minus;Rp{{ number_format($bill->discount, 0, ',', '.') }}</strong></div>@endif
            <div class="invoice-grand-total"><span>{{ __('Total tagihan') }}</span><strong>Rp{{ number_format($bill->total, 0, ',', '.') }}</strong></div>
            @if($showDeposit)
                <div class="invoice-deposit-row"><span>{{ __('DP dibayar') }} ({{ $depositPercentageLabel }}%)</span><strong>Rp{{ number_format($bill->dp_amount, 0, ',', '.') }}</strong></div>
                @if($bill->status === 'unpaid')
                    <div class="invoice-balance-row"><span>{{ __('Sisa tagihan') }}</span><strong>Rp{{ number_format($bill->remainingAmount(), 0, ',', '.') }}</strong></div>
                @elseif($bill->status === 'paid')
                    <div><span>{{ __('Pelunasan') }}</span><strong>Rp{{ number_format(max(0, $bill->total - $bill->dp_amount), 0, ',', '.') }}</strong></div>
                @endif
            @endif
        </div>
        <section class="invoice-payment-summary">
            @if($bill->status === 'paid')
                <h3>{{ __('Pembayaran diterima') }}</h3><p>{{ filled($bill->payment) ? $bill->payment : '-' }} &middot; {{ $bill->paid_at?->locale(app()->getLocale())->translatedFormat('d F Y, H:i') ?? '-' }}</p>@if($bill->user_id)<p>+{{ number_format($bill->transaction?->pointEntry?->points ?? 0, 0, ',', '.') }} {{ __('poin tercatat untuk member.') }}</p>@endif
            @elseif($bill->isPartiallyPaid())
                <h3>{{ __('DP diterima, menunggu pelunasan') }}</h3><p>{{ __('DP sebesar Rp:amount sudah dibayar', ['amount' => number_format($bill->paidAmount(), 0, ',', '.')]) }}@if($bill->dp_paid_at) {{ __('pada :date', ['date' => $bill->dp_paid_at->locale(app()->getLocale())->translatedFormat('d F Y')]) }}@endif. {{ __('Sisa tagihan Rp:amount menunggu pembayaran.', ['amount' => number_format($bill->remainingAmount(), 0, ',', '.')]) }}</p><p>{{ __('Hubungi pengelola untuk detail pelunasan. Simpan nomor invoice sebagai referensi konfirmasi.') }}</p>
            @elseif($bill->status === 'unpaid')
                <h3>{{ __('Menunggu pembayaran') }}</h3><p>{{ __('Hubungi pengelola untuk detail pembayaran. Simpan nomor invoice sebagai referensi konfirmasi.') }}</p>
            @else
                <h3>{{ __('Tagihan dibatalkan') }}</h3><p>{{ __('Invoice ini tidak berlaku untuk pembayaran.') }}</p>
            @endif
        </section>
        <footer class="invoice-document-footer"><strong>{{ __('Terima kasih sudah memilih') }} {{ $issuer }}.</strong><span>{{ __('Good things add up.') }}</span></footer>
    </article>
    @if($admin && $bill->status === 'unpaid' && ($showControls ?? true))
        <section class="neo-card bill-controls">
            <h2>{{ $bill->isPartiallyPaid() ? 'Konfirmasi pelunasan' : 'Konfirmasi pembayaran' }}</h2>
            <p class="muted">Konfirmasi setelah dana diterima.
                @if($bill->isPartiallyPaid())Sisa tagihan <strong>Rp{{ number_format($bill->remainingAmount(), 0, ',', '.') }}</strong> dari total Rp{{ number_format($bill->total, 0, ',', '.') }} (DP {{ $depositPercentageLabel }}%).@endif
                @if($bill->user_id)Transaksi dan poin dicatat satu kali saat invoice lunas.@elsePelanggan tanpa akun tidak mendapatkan poin member.@endif
            </p>
            <form method="post" action="{{ route('admin.bills.pay', $bill) }}" data-confirm="Konfirmasi pelunasan Rp{{ number_format($bill->remainingAmount(), 0, ',', '.') }} untuk invoice ini?">
                @csrf
                <div class="form-field"><label for="payment">Metode pembayaran</label><select name="payment" id="payment" required><option value="QRIS">QRIS</option><option value="Transfer bank">{{ __('Transfer bank') }}</option><option value="Tunai">{{ __('Tunai') }}</option><option value="Kartu debit">{{ __('Kartu debit') }}</option></select></div>
                <button class="button" type="submit">{{ $bill->isPartiallyPaid() ? 'Konfirmasi lunas' : 'Tandai lunas' }} <x-icon name="check" size="16" /></button>
            </form>
            <form class="bill-cancel-form" method="post" action="{{ route('admin.bills.cancel', $bill) }}" data-confirm="Batalkan invoice ini?{{ $bill->isPartiallyPaid() ? ' DP yang sudah dibayar perlu dikembalikan secara manual.' : '' }} Invoice tidak bisa dibayar setelah dibatalkan.">
                @csrf
                <button class="row-action danger" type="submit">Batalkan invoice</button>
            </form>
        </section>
    @endif
    @if($bill->tracking && !($embedded ?? false))<a class="small-link bill-tracking-link" href="{{ $admin ? route('admin.tracking.show', $bill->tracking) : route('membership.tracking.show', $bill->tracking) }}">{{ __('Lihat pesanan terkait') }} <x-icon name="arrow-up-right" size="16" /></a>@endif
</div>
