<dialog id="order-edit-dialog" class="neo-dialog order-dialog order-edit-dialog" aria-labelledby="order-edit-title" @if($errors->any() && !$errors->has('payment') && !$errors->has('bill')) data-open-on-error @endif>
    <div class="dialog-header"><h2 id="order-edit-title">Kelola tracking pesanan</h2><button class="detail-button" type="button" data-order-close aria-label="Tutup pengelolaan pesanan"><x-icon name="close" /></button></div>
    <p class="order-dialog-intro">Perbarui rincian dan perkembangan pesanan untuk pelanggan.</p>
    @if($errors->any() && !$errors->has('payment') && !$errors->has('bill'))<div class="auth-errors" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @include('admin.tracking.order-form', ['inDialog' => true])
</dialog>
<dialog id="order-access-dialog" class="neo-dialog order-dialog" aria-labelledby="order-access-title">
    <div class="dialog-header"><h2 id="order-access-title">Kode tracking</h2><button class="detail-button" type="button" data-order-close aria-label="Tutup kode tracking"><x-icon name="close" /></button></div>
    <p class="order-dialog-intro">{{ $tracking->public_enabled ? 'Akses publik aktif. Bagikan kode ini kepada pelanggan untuk mengecek perkembangan pesanan.' : 'Akses publik belum aktif. Aktifkan melalui Kelola tracking bila kode akan dibagikan kepada pelanggan.' }}</p>
    <div class="order-code"><code>{{ $tracking->public_code }}</code><button class="button button-neutral" type="button" data-copy-reference="{{ $tracking->public_code }}">Salin</button></div>
    <span class="sr-only" data-copy-status role="status"></span>
    <p class="order-dialog-intro">Kode hanya menampilkan status pesanan. Identitas, catatan pribadi, dan bill tetap berada di akun pelanggan.</p>
</dialog>
@if($tracking->bill?->status === 'unpaid')
    <dialog id="order-payment-dialog" class="neo-dialog order-dialog" aria-labelledby="order-payment-dialog-title" @if($errors->has('payment')) data-open-on-error @endif>
        <div class="dialog-header"><h2 id="order-payment-dialog-title">Konfirmasi pembayaran</h2><button class="detail-button" type="button" data-order-close aria-label="Tutup konfirmasi pembayaran"><x-icon name="close" /></button></div>
        <p class="order-dialog-intro">Pastikan pembayaran <strong>Rp{{ number_format($tracking->bill->total, 0, ',', '.') }}</strong> telah diterima. @if($tracking->user_id)Poin member dicatat setelah konfirmasi.@endif</p>
        <form class="admin-form" method="post" action="{{ route('admin.bills.pay', $tracking->bill) }}">
            @csrf
            <div class="form-field"><label for="payment">Metode pembayaran</label><select id="payment" name="payment" required>@foreach(['QRIS', 'Transfer bank', 'Tunai', 'Kartu debit'] as $method)<option value="{{ $method }}" @selected(old('payment') === $method)>{{ $method }}</option>@endforeach</select>@error('payment')<small class="danger">{{ $message }}</small>@enderror</div>
            <div class="form-actions"><button class="button button-neutral" type="button" data-order-close>Batal</button><button class="button" type="submit">Konfirmasi lunas <x-icon name="check" size="16" /></button></div>
        </form>
    </dialog>
@endif
@if($tracking->events->count() > 3)
    <dialog id="order-history-dialog" class="neo-dialog order-dialog order-edit-dialog" aria-labelledby="order-history-dialog-title">
        <div class="dialog-header"><h2 id="order-history-dialog-title">Semua riwayat pesanan</h2><button class="detail-button" type="button" data-order-close aria-label="Tutup riwayat pesanan"><x-icon name="close" /></button></div>
        @include('shared.timeline', ['events' => $tracking->events])
    </dialog>
@endif
