@extends('layouts.member')
@section('title', 'Keamanan akun')
@section('meta-description', 'Kelola metode login dan kata sandi akun membership.')
@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow"><span class="tiny-star">✳</span> ACCOUNT SECURITY</p>
            <h1>{{ $forceSetup ? 'Buat kata sandi baru' : 'Keamanan akun' }}<span class="heading-dot">.</span></h1>
            <p class="page-description">Kelola cara masuk ke akun member tanpa mengubah identitas membership-mu.</p>
        </div>
        <a class="button button-neutral" href="{{ route('membership.profile') }}">Kembali ke profil <x-icon name="arrow-right" size="16" /></a>
    </div>

    @if($forceSetup)
        <div class="portal-alert success" role="status">
            <x-icon name="info" size="18" />
            <span>Kamu masuk menggunakan kata sandi sementara. Buat kata sandi sendiri sebelum melanjutkan ke area member.</span>
        </div>
    @endif

    @include('shared.alerts')

    <div class="portal-grid">
        <section class="neo-card portal-card" aria-labelledby="login-methods-title">
            <div class="card-heading">
                <div>
                    <h2 id="login-methods-title">Metode login</h2>
                    <p class="muted">Email member tetap menjadi identitas utama akun.</p>
                </div>
            </div>

            <div class="list-row">
                <div>
                    <strong>Email</strong>
                    <small>{{ $user->email }}</small>
                </div>
                <span class="badge member-badge">Aktif</span>
            </div>

            <div class="list-row">
                <div>
                    <strong>Google</strong>
                    @if($user->google_id)
                        <small>Akun Google sudah tertaut dan dapat digunakan untuk masuk.</small>
                    @else
                        <small>Masuk lewat tombol Google menggunakan email yang sama untuk menautkan akun secara otomatis.</small>
                    @endif
                </div>
                <span class="badge {{ $user->google_id ? 'success-badge' : 'member-badge' }}">{{ $user->google_id ? 'Terhubung' : 'Belum terhubung' }}</span>
            </div>

            <div class="list-row">
                <div>
                    <strong>Kata sandi</strong>
                    <small>{{ $user->password_setup_at ? 'Sudah dibuat sendiri.' : 'Belum pernah dibuat sendiri.' }}</small>
                </div>
                <span class="badge {{ $user->password_setup_at ? 'success-badge' : 'member-badge' }}">{{ $user->password_setup_at ? 'Aktif' : 'Perlu dibuat' }}</span>
            </div>

            @if($authenticatedViaGoogle && ! $user->password_setup_at)
                <p class="form-help">Kamu sedang masuk lewat Google. Kredensial sementara sebelumnya sudah dinonaktifkan. Membuat kata sandi di sini bersifat opsional dan berguna sebagai metode login cadangan.</p>
            @endif
        </section>

        <section class="neo-card portal-card" aria-labelledby="password-form-title">
            <h2 id="password-form-title">{{ $user->password_setup_at ? 'Ubah kata sandi' : 'Buat kata sandi' }}</h2>
            <p class="muted">Gunakan minimal 8 karakter. Setelah disimpan, kata sandi ini dapat digunakan bersama email member untuk login.</p>

            <form class="admin-form" method="post" action="{{ route('membership.account.password.update') }}">
                @csrf
                @method('PUT')

                @if($user->password_setup_at)
                    <div class="form-field">
                        <label for="current_password">Kata sandi saat ini</label>
                        <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                    </div>
                @endif

                <div class="form-grid">
                    <div class="form-field">
                        <label for="password">Kata sandi baru</label>
                        <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>
                    </div>
                    <div class="form-field">
                        <label for="password_confirmation">Konfirmasi kata sandi baru</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="button" type="submit">{{ $forceSetup ? 'Simpan dan lanjutkan' : ($user->password_setup_at ? 'Perbarui kata sandi' : 'Buat kata sandi') }} <x-icon name="check" size="16" /></button>
                </div>
            </form>
        </section>
    </div>
@endsection
