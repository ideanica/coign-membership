@extends('layouts.portal')
@section('title', $tracking->exists ? 'Kelola pesanan' : 'Buat pesanan')
@section('description', 'Kelola pesanan member maupun pelanggan tanpa akun, lalu bagikan kode tracking publik.')
@push('styles')<link rel="stylesheet" href="{{ asset('css/bill.css') }}">@endpush
@push('scripts')<script src="{{ asset('js/tracking.js') }}" defer></script>@endpush
@section('content')
    <div class="portal-grid">
        <form class="neo-card portal-card admin-form" method="post" action="{{ $tracking->exists ? route('admin.tracking.update', $tracking) : route('admin.tracking.store') }}">
            @csrf
            @if($tracking->exists)
                @method('PUT')
                <p class="tracking-ref">{{ $tracking->reference }}</p>
                <div class="form-help"><strong>Kode pesanan publik</strong><p style="margin:10px 0;overflow-wrap:anywhere">{{ $tracking->public_code }}</p><button type="button" class="button button-neutral" data-copy-reference="{{ $tracking->public_code }}">Salin</button><span class="sr-only" data-copy-status role="status"></span><p style="margin-top:12px">Pelanggan memasukkan kode ini di <a class="small-link" href="{{ route('public.tracking.index') }}">tracking publik</a>. Kode hanya berfungsi jika akses publik diaktifkan.</p></div>
                <div class="form-field"><label>Member</label><p>{{ $tracking->user?->name ?? 'Pelanggan tanpa akun' }}</p><small>Pemilik tracking tetap sama untuk menjaga riwayat.</small></div>
            @else
                <div class="form-field"><label for="user_id">Member (opsional)</label><select name="user_id" id="user_id"><option value="">Pelanggan tanpa akun</option>@foreach($members as $member)<option value="{{ $member->id }}" @selected(old('user_id') == $member->id)>{{ $member->name }} · {{ $member->email }}</option>@endforeach</select><small>Pilih akun jika pelanggan sudah menjadi member.</small></div>
            @endif
            @if($tracking->exists && $tracking->bill)
                <div class="form-field"><label>Layanan pesanan</label><p>{{ $tracking->bill->service_name ?? $tracking->service?->name ?? 'Layanan khusus' }}</p><input type="hidden" name="service_id" value="{{ $tracking->service_id }}"><small>Layanan sudah tercatat pada bill yang diterbitkan.</small></div>
            @else
            <div class="form-field"><label for="service_id">Layanan</label><select name="service_id" id="service_id"><option value="">Layanan khusus</option>@foreach($services as $service)<option data-price="{{ $service->price }}" value="{{ $service->id }}" @selected(old('service_id', $tracking->service_id) == $service->id)>{{ $service->name }}</option>@endforeach</select></div>
            @endif
            <x-field name="title" label="Judul pesanan" :value="$tracking->title" required maxlength="255" />
            <div class="form-field"><label for="description">Deskripsi pekerjaan</label><textarea name="description" id="description" rows="3" maxlength="4000">{{ old('description', $tracking->description) }}</textarea></div>
            <div class="form-grid"><div class="form-field"><label for="tracking_status_id">Status saat ini</label><select id="tracking_status_id" name="tracking_status_id" required>@foreach($statuses as $status)<option value="{{ $status->id }}" @selected(old('tracking_status_id', $tracking->tracking_status_id) == $status->id)>{{ $status->label }}</option>@endforeach</select></div><x-field name="due_date" label="Target selesai" type="date" :value="$tracking->due_date?->format('Y-m-d')" /></div>
            <div class="form-field"><label for="note">Update untuk member</label><textarea name="note" id="note" rows="3" maxlength="2000" placeholder="Tulis perkembangan terbaru…">{{ old('note') }}</textarea><small>Catatan detail hanya terlihat oleh member dan admin. Tracking publik menampilkan riwayat status tanpa catatan ini.</small></div>
            <input type="hidden" name="public_enabled" value="0"><label class="remember-field"><input type="checkbox" name="public_enabled" value="1" @checked(old('public_enabled', $tracking->public_enabled))> Aktifkan tracking publik untuk kode pesanan ini</label><div class="form-help">Siapa pun yang memiliki kode dapat melihat judul layanan, status, target selesai, dan riwayat status. Nama, email, bill, deskripsi, dan catatan member tidak ditampilkan.</div>
            @if(!$tracking->exists)
                <section class="order-billing" data-order-billing><h2>Rincian harga & bill</h2><p class="muted">Bill diterbitkan otomatis saat pesanan disimpan. Pastikan harga dan pelanggan benar karena rincian ini tidak bisa diedit setelah terbit.</p>
                    <div class="form-grid" style="margin-top:18px"><x-field name="customer_name" label="Nama pelanggan tanpa akun" maxlength="255" placeholder="Isi jika tidak memilih member" /><x-field name="customer_email" label="Email pelanggan (opsional)" type="email" maxlength="255" /></div>
                    <div class="form-grid" style="margin-top:18px"><x-field name="quantity" label="Jumlah" type="number" value="1" required min="1" max="10000" /><x-field name="unit_price" label="Harga yang disepakati (Rp)" type="number" required min="1" max="1000000000">Harga layanan otomatis mengisi kolom ini. Sesuaikan sebelum pesanan diterbitkan.</x-field></div>
                    <div class="form-grid" style="margin-top:18px"><x-field name="discount" label="Diskon (Rp)" type="number" value="0" required min="0" max="10000000000000" /><x-field name="bill_due_date" label="Jatuh tempo pembayaran (opsional)" type="date" /></div>
                    <div class="form-help" style="margin-top:18px">Total bill: <strong data-order-total>Rp0</strong><br>Poin member diberikan setelah pembayaran dikonfirmasi, bukan saat pesanan dibuat.</div>
                </section>
            @elseif($tracking->bill)
                <div class="form-help">Bill pesanan sudah diterbitkan dan rincian harganya terkunci. <a class="small-link" href="{{ route('admin.bills.show', $tracking->bill) }}">Lihat bill {{ $tracking->bill->reference }}</a></div>
            @endif
            <div class="form-actions"><button class="button" type="submit">{{ $tracking->exists ? 'Simpan perkembangan' : 'Buat pesanan & bill' }}</button><a class="button button-neutral" href="{{ route('admin.tracking.index') }}">Kembali</a></div>
        </form>
        @if($tracking->exists)
            <section class="neo-card portal-card"><h2>Timeline tracking</h2><p class="muted">Riwayat update yang terlihat oleh member.</p>@include('shared.timeline', ['events' => $tracking->events])</section>
        @else
            <section class="neo-card portal-card"><h2>Pesanan, tracking, dan bill</h2><p class="muted">Pilih member atau isi nama pelanggan, tentukan layanan dan harga yang disepakati. Setelah disimpan, tracking dan bill dibuat bersama. Periksa rincian harga sebelum menerbitkan bill.</p></section>
        @endif
    </div>
@endsection
