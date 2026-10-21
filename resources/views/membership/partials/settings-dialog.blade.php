<dialog id="member-settings-dialog" class="neo-dialog member-settings-dialog" data-managed-dialog aria-labelledby="member-settings-title" @if(request()->boolean('settings') || old('_account_settings')) data-open-on-error @endif>
    <div class="dialog-header"><div><p class="section-kicker">{{ __('AKUN PRIBADI') }}</p><h2 id="member-settings-title">{{ __('Pengaturan akun') }}</h2></div><button class="detail-button" type="button" data-dialog-close aria-label="{{ __('Tutup pengaturan') }}"><x-icon name="close" /></button></div>
    @if(request()->boolean('settings') || old('_account_settings'))@include('shared.alerts')@endif
    <section class="member-settings-summary" aria-label="{{ __('Keamanan akun') }}">
        <h3>{{ __('Keamanan akun') }}</h3><p><strong>{{ auth()->user()->name }}</strong></p><p>{{ auth()->user()->email }}</p>
        <dl><div><dt>Google</dt><dd>{{ auth()->user()->google_id ? __('Terhubung') : __('Belum terhubung') }}</dd></div><div><dt>{{ __('Kata sandi') }}</dt><dd>{{ auth()->user()->password_setup_at ? __('Aktif') : __('Perlu dibuat') }}</dd></div></dl>
        <p>{{ auth()->user()->google_id ? __('Akun Google sudah tertaut dan dapat digunakan untuk masuk.') : __('Masuk lewat tombol Google menggunakan email yang sama untuk menautkan akun secara otomatis.') }}</p>
    </section>
    <details class="member-settings-section" @if($emailChange || old('_settings_action') === 'email') open @endif>
        <summary>{{ __('Ubah email login') }} <x-icon name="chevron-down" size="17" /></summary>
        <p>{{ __('Email akun hanya berubah setelah kode yang dikirim ke email baru berhasil diverifikasi. Setelah berubah, tautan Google lama dilepas dan sesi di perangkat lain diakhiri.') }}</p>
        @if(!$emailDeliveryAvailable)<p class="form-help" role="status">{{ __('Pengiriman email belum aktif. Hubungi pengelola untuk mengaktifkannya.') }}</p>@endif
        @if($emailChange)
            <div class="form-help"><strong>{{ __('Menunggu verifikasi') }}</strong><p>{{ __('Kode dikirim ke') }} <strong>{{ $emailChange->new_email }}</strong>.</p><p>{{ __('Berlaku sampai') }} {{ $emailChange->expires_at->timezone(config('app.timezone'))->format('H:i') }} ({{ config('app.timezone') }}).</p></div>
            <form class="admin-form" method="post" action="{{ route('membership.account.email.update') }}">@csrf @method('PUT')<input type="hidden" name="_account_settings" value="1"><input type="hidden" name="_settings_action" value="email">
                <div class="form-field"><label for="verification-code">{{ __('Kode verifikasi') }}</label><input id="verification-code" name="verification_code" type="text" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="one-time-code" required @disabled($emailChange->attempts >= \App\Services\EmailChangeService::MAX_ATTEMPTS)><small>{{ __('Masukkan 6 angka dari email baru. Kode berlaku 10 menit, maksimal 5 percobaan.') }}</small></div>
                @if($emailChange->attempts >= \App\Services\EmailChangeService::MAX_ATTEMPTS)<p class="form-help">{{ __('Batas percobaan tercapai. Kirim kode baru.') }}</p>@endif
                <button class="button" type="submit" @disabled($emailChange->attempts >= \App\Services\EmailChangeService::MAX_ATTEMPTS)>{{ __('Verifikasi dan ubah email') }}</button>
            </form>
            <form class="member-email-resend" method="post" action="{{ route('membership.account.email.resend') }}">@csrf<input type="hidden" name="_account_settings" value="1"><input type="hidden" name="_settings_action" value="email"><button class="text-button" type="submit" @disabled(!$emailDeliveryAvailable)>{{ __('Kirim ulang kode') }}</button><small>{{ __('Tunggu 60 detik sebelum mengirim ulang. Kode sebelumnya akan dibatalkan.') }}</small></form>
        @endif
        <form class="admin-form" method="post" action="{{ route('membership.account.email.store') }}">@csrf<input type="hidden" name="_account_settings" value="1"><input type="hidden" name="_settings_action" value="email">
            <div class="form-field"><label for="settings-new-email">{{ __('Email baru') }}</label><input id="settings-new-email" name="new_email" type="email" required maxlength="254" autocomplete="email" value="{{ is_string(old('new_email')) ? old('new_email') : '' }}" @error('new_email') aria-invalid="true" @enderror></div>
            @if(auth()->user()->password_setup_at !== null)<div class="form-field"><label for="email-current-password">{{ __('Kata sandi saat ini') }}</label><input id="email-current-password" name="current_password" type="password" autocomplete="current-password" required></div>@endif
            <button class="button button-neutral" type="submit" @disabled(!$emailDeliveryAvailable)>{{ $emailChange ? __('Kirim kode ke email lain') : __('Kirim kode verifikasi') }}</button>
        </form>
    </details>
    <details class="member-settings-section" @if(old('_settings_action') === 'password') open @endif>
        <summary>{{ auth()->user()->password_setup_at ? __('Ubah kata sandi') : __('Buat kata sandi') }} <x-icon name="chevron-down" size="17" /></summary>
        <p>{{ __('Gunakan minimal 8 karakter. Setelah disimpan, kata sandi ini dapat digunakan bersama email member untuk login.') }}</p>
        <form class="admin-form" method="post" action="{{ route('membership.account.password.update') }}">@csrf @method('PUT')<input type="hidden" name="_account_settings" value="1"><input type="hidden" name="_settings_action" value="password">
            @if(auth()->user()->password_setup_at !== null)<x-field name="current_password" label="{{ __('Kata sandi saat ini') }}" type="password" required autocomplete="current-password" />@endif
            <x-field name="password" label="{{ __('Kata sandi baru') }}" type="password" required minlength="8" autocomplete="new-password" />
            <x-field name="password_confirmation" label="{{ __('Konfirmasi kata sandi baru') }}" type="password" required minlength="8" autocomplete="new-password" />
            <button class="button" type="submit">{{ __('Perbarui kata sandi') }}</button>
        </form>
    </details>
    <div class="member-settings-footer"><button class="button button-neutral" type="button" data-dialog-close>{{ __('Tutup') }}</button><form method="post" action="{{ route('logout') }}">@csrf<button class="button" type="submit">{{ __('Keluar') }} <x-icon name="arrow-up-right" size="16" /></button></form></div>
</dialog>
