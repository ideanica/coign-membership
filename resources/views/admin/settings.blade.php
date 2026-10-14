@extends('layouts.portal')
@section('title', 'Pengaturan')
@section('description', 'Atur identitas membership, kontak pengelola, dan aturan perolehan poin.')
@section('content')
    <form class="neo-card portal-card admin-form" method="post" action="{{ route('admin.settings.update') }}">
        @csrf @method('PUT')
        <x-field name="brand_name" label="Nama membership" :value="$settings['brand_name'] ?? 'Coignitive'" required maxlength="40" />
        <x-field name="support_email" label="Email pengelola" type="email" :value="$settings['support_email'] ?? ''" maxlength="254">Tampil sebagai kontak bantuan di halaman member. Kosongkan jika belum tersedia.</x-field>
        <x-field name="support_whatsapp" label="Nomor WhatsApp" :value="$settings['support_whatsapp'] ?? ''" maxlength="16" placeholder="+6289616366135">Gunakan kode negara. Tombol WhatsApp membuka chat langsung.</x-field>
        <x-field name="support_x" label="Akun X" :value="$settings['support_x'] ?? ''" maxlength="16" placeholder="@coignitive">Username akun X untuk kontak di Home. Kosongkan untuk menyembunyikan.</x-field>
        <x-field name="rupiah_per_point" label="Nilai transaksi untuk 1 poin (Rp)" type="number" :value="$settings['rupiah_per_point'] ?? 1000" min="1" max="1000000000" required>Contoh: 1000 berarti setiap kelipatan Rp1.000 menghasilkan 1 poin.</x-field>
        <div class="form-help">Rasio ini dipakai saat mode perolehan poin adalah rasio. Kelola mode dan batas hadiah pembelian di <a class="small-link" href="{{ route('admin.point-rules.index') }}">Poin otomatis</a>. Perubahan hanya berlaku untuk transaksi berikutnya.</div>
        <div class="form-actions"><button class="button" type="submit">Simpan pengaturan <x-icon name="check" size="16" /></button></div>
    </form>
@endsection
