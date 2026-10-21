@extends('layouts.portal')
@section('title', 'Pengaturan')
@section('description', 'Kelola profil, metode login, dan pengaturan sesuai akses akunmu.')
@include('admin.workspace-assets')
@push('styles')<link rel="stylesheet" href="{{ asset('css/admin-preferences.css') }}?v={{ filemtime(public_path('css/admin-preferences.css')) }}">@endpush
@section('content')
    <div class="settings-grid">
        @if(auth()->user()->isSuperAdmin())
        <section class="neo-card settings-card" aria-labelledby="settings-identity-title">
            <div class="settings-card-heading"><span class="settings-icon"><x-icon name="spark" size="23" /></span><div><p class="section-kicker">IDENTITAS</p><h2 id="settings-identity-title">Brand & tampilan</h2></div></div>
            <p class="settings-brand">{{ $settings['brand_name'] ?? 'Coignitive' }}</p><p class="settings-description">Nama ini dipakai sebagai identitas website dan pada bill yang diterbitkan.</p>
            <button class="button button-neutral" type="button" data-dialog-open="settings-identity-dialog">Edit identitas <x-icon name="arrow-up-right" size="16" /></button>
        </section>
        <section class="neo-card settings-card" aria-labelledby="settings-contact-title">
            <div class="settings-card-heading"><span class="settings-icon settings-icon-pink"><x-icon name="mail" size="23" /></span><div><p class="section-kicker">KONTAK PUBLIK</p><h2 id="settings-contact-title">Cara pelanggan menghubungi tim</h2></div></div>
            <dl class="settings-contact-list"><div><dt>Email</dt><dd>{{ filled($settings['support_email'] ?? '') ? $settings['support_email'] : 'Belum diatur' }}</dd></div><div><dt>WhatsApp</dt><dd>{{ filled($settings['support_whatsapp'] ?? '') ? $settings['support_whatsapp'] : 'Belum diatur' }}</dd></div><div><dt>Akun X</dt><dd>{{ filled($settings['support_x'] ?? '') ? $settings['support_x'] : 'Belum diatur' }}</dd></div></dl>
            <button class="button button-neutral" type="button" data-dialog-open="settings-contact-dialog">Edit kontak <x-icon name="arrow-up-right" size="16" /></button>
        </section>
            <section class="neo-card settings-card settings-points" aria-labelledby="settings-points-title">
                <div class="settings-points-copy"><div class="settings-card-heading"><span class="settings-icon settings-icon-blue"><x-icon name="star" size="23" /></span><div><p class="section-kicker">POIN & HADIAH</p><h2 id="settings-points-title">Aturan perolehan poin</h2></div></div>
                    @if(($settings['points_mode'] ?? 'ratio') === 'tiers')<p class="settings-value">Aturan bertingkat</p><p class="settings-description">Poin mengikuti batas pembelian tertinggi yang terpenuhi. Kelola nominal pembelian dan hadiahnya di halaman Poin otomatis.</p>
                    @else<p class="settings-value">Rp{{ number_format($settings['rupiah_per_point'] ?? 1000, 0, ',', '.') }} <span>= 1 poin</span></p><p class="settings-description">Mode rasio aktif. Setiap kelipatan nominal ini menghasilkan 1 poin dari pembayaran yang selesai.</p>@endif
                </div>
                <div class="settings-card-actions">
                    @if(($settings['points_mode'] ?? 'ratio') === 'ratio')<button class="button" type="button" data-dialog-open="settings-points-dialog">Edit rasio poin <x-icon name="star" size="16" /></button>@endif
                    <a class="button button-neutral" href="{{ route('admin.point-rules.index') }}">Kelola poin otomatis <x-icon name="arrow-up-right" size="16" /></a>
                </div>
            </section>
        @endif
    </div>
    <div class="settings-grid settings-personal">
        <section class="neo-card settings-card" aria-labelledby="settings-account-title">
            <div class="settings-card-heading"><span class="settings-icon"><x-icon name="user" size="23" /></span><div><p class="section-kicker">AKUN PRIBADI</p><h2 id="settings-account-title">Profil admin</h2></div></div>
            <dl class="settings-contact-list">
                <div><dt>Nama</dt><dd>{{ auth()->user()->name }}</dd></div>
                <div><dt>Email login</dt><dd>{{ auth()->user()->email }}</dd></div>
                <div><dt>Role</dt><dd>{{ auth()->user()->isSuperAdmin() ? 'Superadmin' : 'Admin' }}</dd></div>
                <div><dt>Status akun</dt><dd>{{ auth()->user()->is_active ? 'Aktif' : 'Nonaktif' }}</dd></div>
                <div><dt>Bergabung</dt><dd>{{ auth()->user()->created_at?->format('d M Y') ?? '-' }}</dd></div>
            </dl>
            <p class="settings-description">{{ auth()->user()->isSuperAdmin() ? 'Kamu dapat mengelola seluruh menu, termasuk member, poin, brand, dan manajemen admin.' : 'Kamu dapat mengelola katalog dan pesanan. Member, poin, identitas brand, kontak brand, dan manajemen admin dikelola oleh superadmin.' }}</p>
            <button class="button button-neutral" type="button" data-dialog-open="settings-profile-dialog">Edit profil <x-icon name="user" size="16" /></button>
        </section>
        <section class="neo-card settings-card" aria-labelledby="settings-login-title">
            <div class="settings-card-heading"><span class="settings-icon settings-icon-blue"><x-icon name="info" size="23" /></span><div><p class="section-kicker">KEAMANAN</p><h2 id="settings-login-title">Metode login</h2></div></div>
            <dl class="settings-contact-list">
                <div><dt>Kata sandi</dt><dd>Login dengan email dan kata sandi akunmu.</dd></div>
                <div><dt>Google</dt><dd><span class="badge">{{ auth()->user()->google_id ? 'Terhubung' : 'Belum terhubung' }}</span></dd></div>
            </dl>
            @if(auth()->user()->google_id)
                <p class="settings-description">Akun Google sudah tertaut. Untuk masuk berikutnya, pilih <strong>Lanjutkan dengan Google</strong> di halaman login lalu gunakan akun Google yang sudah kamu hubungkan. Kamu tetap dapat masuk dengan kata sandi.</p>
            @else
                <p class="settings-description">Hubungkan Google dengan masuk menggunakan akun Google beremail <strong>{{ auth()->user()->email }}</strong>. Role dan akses admin tetap mengikuti akun ini.</p>
            @endif
            <div class="settings-card-actions">
                <button class="button button-neutral" type="button" data-dialog-open="settings-google-dialog">{{ auth()->user()->google_id ? 'Panduan login Google' : 'Cara hubungkan Google' }}</button>
                <button class="button" type="button" data-dialog-open="settings-password-dialog">Ubah kata sandi</button>
            </div>
        </section>
    </div>
    <dialog id="settings-google-dialog" class="neo-dialog workspace-dialog" data-managed-dialog aria-labelledby="settings-google-title">
        <div class="dialog-header"><h2 id="settings-google-title">{{ auth()->user()->google_id ? 'Masuk dengan akun Google' : 'Hubungkan akun Google' }}</h2><button class="detail-button" type="button" data-dialog-close aria-label="Tutup panduan Google"><x-icon name="close" /></button></div>
        <p class="workspace-dialog-intro">{{ auth()->user()->google_id ? 'Google sudah terhubung ke akun admin ini.' : 'Penautan dilakukan saat login Google pertama kali, menggunakan email yang sama dengan akun admin.' }}</p>
        <ol class="settings-google-steps">
            <li>Pastikan akun Google yang akan digunakan memiliki email <strong>{{ auth()->user()->email }}</strong>. Jika email profil berbeda, ubah terlebih dahulu melalui <strong>Edit profil</strong>.</li>
            <li>Klik <strong>Keluar dan buka login</strong> di bawah ini untuk mengakhiri sesi admin saat ini.</li>
            <li>Di halaman login, klik <strong>Lanjutkan dengan Google</strong>, lalu pilih akun Google dengan email tersebut dan selesaikan proses masuk.</li>
            <li>Setelah berhasil, kamu masuk ke dashboard admin. Buka <strong>Pengaturan</strong> untuk memastikan status Google menjadi <strong>Terhubung</strong>.</li>
        </ol>
        <p class="workspace-dialog-intro">Email Google yang berbeda tidak akan ditautkan ke akun ini. Jika muncul pesan email belum terdaftar, kembali ke login dan pilih akun Google yang benar. Mengganti email profil akan melepas tautan Google lama.</p>
        @if(!filled(config('services.google.client_id')) || !filled(config('services.google.client_secret')))
            <p class="workspace-dialog-intro" role="status">Login Google belum diaktifkan di website ini. Hubungi pengelola untuk mengaktifkannya; sementara gunakan email dan kata sandi.</p>
        @endif
        <div class="form-actions"><button class="button button-neutral" type="button" data-dialog-close>Tutup</button><form method="post" action="{{ route('logout') }}">@csrf<button class="button" type="submit">Keluar dan buka login <x-icon name="arrow-up-right" size="16" /></button></form></div>
    </dialog>
    <dialog id="settings-profile-dialog" class="neo-dialog workspace-dialog" data-managed-dialog aria-labelledby="settings-profile-title" @if($errors->any() && old('_settings_section') === 'profile') data-open-on-error @endif>
        <div class="dialog-header"><h2 id="settings-profile-title">Edit profil akun</h2><button class="detail-button" type="button" data-dialog-close aria-label="Tutup edit profil"><x-icon name="close" /></button></div>
        <p class="workspace-dialog-intro">Jika email diganti, tautan Google lama dilepas. Gunakan email baru untuk masuk berikutnya.</p>
        <form class="admin-form" method="post" action="{{ route('admin.settings.profile.update') }}">@csrf @method('PUT')<input type="hidden" name="_settings_section" value="profile">
            <x-field name="name" label="Nama" :value="auth()->user()->name" required maxlength="100" />
            <x-field name="email" label="Email akun" type="email" :value="auth()->user()->email" required maxlength="254" />
            <div class="form-actions"><button class="button button-neutral" type="button" data-dialog-close>Batal</button><button class="button" type="submit">Simpan profil</button></div>
        </form>
    </dialog>
    <dialog id="settings-password-dialog" class="neo-dialog workspace-dialog" data-managed-dialog aria-labelledby="settings-password-title" @if($errors->any() && old('_settings_section') === 'password') data-open-on-error @endif>
        <div class="dialog-header"><h2 id="settings-password-title">Ubah kata sandi</h2><button class="detail-button" type="button" data-dialog-close aria-label="Tutup ubah kata sandi"><x-icon name="close" /></button></div>
        <form class="admin-form" method="post" action="{{ route('admin.settings.password.update') }}">@csrf @method('PUT')<input type="hidden" name="_settings_section" value="password">
            <x-field name="current_password" label="Kata sandi saat ini" type="password" required autocomplete="current-password" />
            <x-field name="password" label="Kata sandi baru" type="password" required minlength="8" autocomplete="new-password" />
            <x-field name="password_confirmation" label="Konfirmasi kata sandi" type="password" required minlength="8" autocomplete="new-password" />
            <div class="form-actions"><button class="button button-neutral" type="button" data-dialog-close>Batal</button><button class="button" type="submit">Simpan kata sandi</button></div>
        </form>
    </dialog>
    @if(auth()->user()->isSuperAdmin())

    <dialog id="settings-identity-dialog" class="neo-dialog workspace-dialog" data-managed-dialog aria-labelledby="settings-identity-dialog-title" @if($errors->any() && old('_settings_section') === 'identity') data-open-on-error @endif>
        <div class="dialog-header"><h2 id="settings-identity-dialog-title">Edit identitas brand</h2><button class="detail-button" type="button" data-dialog-close aria-label="Tutup edit identitas"><x-icon name="close" /></button></div>
        <p class="workspace-dialog-intro">Perubahan nama diterapkan ke website. Identitas pada bill yang sudah diterbitkan tetap mengikuti data saat penerbitan.</p>
        @if($errors->any() && old('_settings_section') === 'identity')@include('shared.alerts')@endif
        <form class="admin-form" method="post" action="{{ route('admin.settings.update') }}">@csrf @method('PUT')<input type="hidden" name="_settings_section" value="identity">
            <x-field name="brand_name" label="Nama brand" :value="$settings['brand_name'] ?? 'Coignitive'" required maxlength="40" />
            <div class="form-actions"><button class="button button-neutral" type="button" data-dialog-close>Batal</button><button class="button" type="submit">Simpan identitas <x-icon name="check" size="16" /></button></div>
        </form>
    </dialog>
    <dialog id="settings-contact-dialog" class="neo-dialog workspace-dialog" data-managed-dialog aria-labelledby="settings-contact-dialog-title" @if($errors->any() && old('_settings_section') === 'contact') data-open-on-error @endif>
        <div class="dialog-header"><h2 id="settings-contact-dialog-title">Edit kontak publik</h2><button class="detail-button" type="button" data-dialog-close aria-label="Tutup edit kontak"><x-icon name="close" /></button></div>
        <p class="workspace-dialog-intro">Kontak ini tampil sebagai jalur bantuan dan pemesanan. Kosongkan kontak yang belum digunakan.</p>
        @if($errors->any() && old('_settings_section') === 'contact')@include('shared.alerts')@endif
        <form class="admin-form" method="post" action="{{ route('admin.settings.update') }}">@csrf @method('PUT')<input type="hidden" name="_settings_section" value="contact">
            <x-field name="support_email" label="Email pengelola" type="email" :value="$settings['support_email'] ?? ''" maxlength="254">Email bantuan dan konsultasi pemesanan.</x-field>
            <x-field name="support_whatsapp" label="Nomor WhatsApp" :value="$settings['support_whatsapp'] ?? ''" maxlength="16" placeholder="+6281234567890">Gunakan kode negara, misalnya +62 untuk Indonesia.</x-field>
            <x-field name="support_x" label="Akun X" :value="$settings['support_x'] ?? ''" maxlength="16" placeholder="@coignitive">Isi username, bukan alamat URL profil.</x-field>
            <div class="form-actions"><button class="button button-neutral" type="button" data-dialog-close>Batal</button><button class="button" type="submit">Simpan kontak <x-icon name="check" size="16" /></button></div>
        </form>
    </dialog>
        <dialog id="settings-points-dialog" class="neo-dialog workspace-dialog" data-managed-dialog aria-labelledby="settings-points-dialog-title" @if($errors->any() && old('_settings_section') === 'points') data-open-on-error @endif>
            <div class="dialog-header"><h2 id="settings-points-dialog-title">Edit rasio poin</h2><button class="detail-button" type="button" data-dialog-close aria-label="Tutup edit rasio poin"><x-icon name="close" /></button></div>
            <p class="workspace-dialog-intro">Rasio berlaku saat mode rasio aktif dan hanya memengaruhi pembayaran berikutnya. Poin yang sudah dicatat tidak dihitung ulang.</p>
            @if($errors->any() && old('_settings_section') === 'points')@include('shared.alerts')@endif
            <form class="admin-form" method="post" action="{{ route('admin.settings.update') }}">@csrf @method('PUT')<input type="hidden" name="_settings_section" value="points">
                <x-field name="rupiah_per_point" label="Nilai transaksi untuk 1 poin (Rp)" type="number" :value="$settings['rupiah_per_point'] ?? 1000" min="1" max="1000000000" required />
                <div class="form-actions"><button class="button button-neutral" type="button" data-dialog-close>Batal</button><button class="button" type="submit">Simpan rasio <x-icon name="check" size="16" /></button></div>
            </form>
        </dialog>
    @endif
@endsection