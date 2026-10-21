@php
    $admin = request()->routeIs('admin.*');
    $customerName = $bill->customer_name ?? $bill->user?->name ?? __('Pelanggan tanpa akun');
    $customerEmail = $bill->customer_email ?? $bill->user?->email;
    $customerNumber = $bill->customer_number ?? $bill->user?->member_number;
    $issuer = $bill->issuer_name ?? $settings['brand_name'] ?? 'Coignitive';
    $email = $bill->issuer_email ?? $settings['support_email'] ?? '';
@endphp
<div class="bill-document">
    <p class="invoice-screen-note"><x-icon name="receipt" size="15" /> {{ __('Siap cetak Letter &middot; Rincian tagihan terkunci setelah diterbitkan.') }}</p>
    <article class="neo-card invoice-card invoice-sheet">
        <header class="invoice-document-header">
            <div><div class="invoice-document-logo"><x-brand-logo full :settings="['brand_name' => $issuer]" /></div><strong class="invoice-issuer">{{ $issuer }}</strong><p>{{ filled($email) ? $email : '-' }}</p></div>
            <div class="invoice-document-id"><p class="invoice-kicker">{{ __('BILL / TAGIHAN') }}</p><h2>{{ __('Untuk ide') }}<br>{{ __('& momenmu.') }}</h2><p class="invoice-reference">{{ $bill->reference }}</p><span class="badge invoice-status status-{{ $bill->status }}">{{ __(\App\Models\Bill::STATUS_LABELS[$bill->status]) }}</span></div>
        </header>
        <div class="invoice-document-meta">
            <section><h3>{{ __('Ditagihkan kepada') }}</h3><strong>{{ $customerName }}</strong><p>{{ filled($customerEmail) ? $customerEmail : '-' }}</p><p>Member: {{ filled($customerNumber) ? $customerNumber : '-' }}</p></section>
            <section><h3>{{ __('Informasi tagihan') }}</h3><dl><div><dt>{{ __('Diterbitkan') }}</dt><dd>{{ $bill->created_at->locale(app()->getLocale())->translatedFormat('d F Y') }}</dd></div><div><dt>{{ __('Jatuh tempo') }}</dt><dd>{{ $bill->due_date?->locale(app()->getLocale())->translatedFormat('d F Y') ?? '-' }}</dd></div><div><dt>{{ __('Pesanan') }}</dt><dd>{{ $bill->tracking?->reference ?? '-' }}</dd></div></dl></section>
        </div>
        <div class="invoice-items"><div class="invoice-items-heading"><span>{{ __('Layanan / pekerjaan') }}</span><span>Subtotal</span></div><div class="invoice-item"><div><h3>{{ $bill->title }}</h3><p>{{ $bill->service_name ?? $bill->service?->name ?? __('Layanan khusus') }}</p><p>{{ number_format($bill->quantity, 0, ',', '.') }} &times; {{ 'Rp'.number_format($bill->unit_price, 0, ',', '.') }}</p></div><strong>{{ 'Rp'.number_format($bill->quantity * $bill->unit_price, 0, ',', '.') }}</strong></div></div>
        <div class="invoice-document-totals"><div><span>Subtotal</span><strong>Rp{{ number_format($bill->quantity * $bill->unit_price, 0, ',', '.') }}</strong></div><div><span>{{ __('Diskon') }}</span><strong>&minus;Rp{{ number_format($bill->discount, 0, ',', '.') }}</strong></div><div class="invoice-grand-total"><span>{{ __('Total tagihan') }}</span><strong>Rp{{ number_format($bill->total, 0, ',', '.') }}</strong></div></div>
        <section class="invoice-payment-summary"><h3>{{ $bill->status === 'paid' ? __('Pembayaran diterima') : ($bill->status === 'cancelled' ? __('Tagihan dibatalkan') : __('Menunggu pembayaran')) }}</h3>
            @if($bill->status === 'paid')<p>{{ filled($bill->payment) ? $bill->payment : '-' }} &middot; {{ $bill->paid_at?->locale(app()->getLocale())->translatedFormat('d F Y, H:i') ?? '-' }}</p>@if($bill->user_id)<p>+{{ number_format($bill->transaction?->pointEntry?->points ?? 0, 0, ',', '.') }} {{ __('poin tercatat untuk member.') }}</p>@endif
            @elseif($bill->status === 'unpaid')<p>{{ __('Hubungi pengelola untuk detail pembayaran. Simpan nomor bill sebagai referensi konfirmasi.') }}</p>
            @else<p>{{ __('Bill ini tidak berlaku untuk pembayaran.') }}</p>@endif
        </section>
        <footer class="invoice-document-footer"><strong>{{ __('Terima kasih sudah memilih') }} {{ $issuer }}.</strong><span>{{ __('Good things add up.') }}</span></footer>
    </article>
    @if($admin && $bill->status === 'unpaid' && ($showControls ?? true))
        <section class="neo-card bill-controls"><h2>Konfirmasi pembayaran</h2><p class="muted">Konfirmasi setelah dana diterima. @if($bill->user_id)Transaksi dan poin dicatat satu kali.@elsePelanggan tanpa akun tidak mendapatkan poin member.@endif</p><form method="post" action="{{ route('admin.bills.pay', $bill) }}" data-confirm="Konfirmasi pembayaran Rp{{ number_format($bill->total, 0, ',', '.') }} untuk bill ini?">@csrf<div class="form-field"><label for="payment">Metode pembayaran</label><select name="payment" id="payment" required><option value="QRIS">QRIS</option><option value="Transfer bank">{{ __('Transfer bank') }}</option><option value="Tunai">{{ __('Tunai') }}</option><option value="Kartu debit">{{ __('Kartu debit') }}</option></select></div><button class="button" type="submit">Tandai lunas <x-icon name="check" size="16" /></button></form><form class="bill-cancel-form" method="post" action="{{ route('admin.bills.cancel', $bill) }}" data-confirm="Batalkan bill ini? Bill tidak bisa dibayar setelah dibatalkan.">@csrf<button class="row-action danger" type="submit">Batalkan bill</button></form></section>
    @endif
    @if($bill->tracking && !($embedded ?? false))<a class="small-link bill-tracking-link" href="{{ $admin ? route('admin.tracking.show', $bill->tracking) : route('membership.tracking.show', $bill->tracking) }}">{{ __('Lihat pesanan terkait') }} <x-icon name="arrow-up-right" size="16" /></a>@endif
</div>
