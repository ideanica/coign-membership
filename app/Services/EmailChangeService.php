<?php

namespace App\Services;

use App\Mail\EmailChangeCode;
use App\Models\EmailChange;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class EmailChangeService
{
    public const LIFETIME_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    public function deliveryAvailable(): bool
    {
        $mailer = config('mail.default');
        $transport = config('mail.mailers.'.$mailer.'.transport');
        if (! filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL) || str_ends_with((string) config('mail.from.address'), '@example.com')) {
            return false;
        }

        return match ($transport) {
            'smtp' => filled(config('mail.mailers.'.$mailer.'.url')) || filled(config('mail.mailers.'.$mailer.'.host')) && ! in_array(config('mail.mailers.'.$mailer.'.host'), ['localhost', '127.0.0.1', 'mailpit'], true),
            'resend' => filled(config('services.resend.key')),
            'postmark' => filled(config('services.postmark.key')),
            'ses', 'ses-v2' => filled(config('services.ses.key')) && filled(config('services.ses.secret')),
            default => false,
        };
    }

    public function pendingFor(User $user, string $sessionToken): ?EmailChange
    {
        if ($sessionToken === '') {
            return null;
        }

        return EmailChange::where('user_id', $user->id)->where('old_email', $user->email)
            ->where('session_hash', hash('sha256', $sessionToken))->where('expires_at', '>', now())->first();
    }

    public function send(User $user, string $newEmail, string $sessionToken, bool $resend = false): void
    {
        if (! $this->deliveryAvailable()) {
            throw ValidationException::withMessages(['new_email' => __('Pengiriman email belum aktif. Hubungi pengelola untuk mengaktifkannya.')]);
        }
        try {
            DB::transaction(function () use ($user, $newEmail, $sessionToken, $resend): void {
                $lockedUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                $pending = EmailChange::where('user_id', $user->id)->lockForUpdate()->first();
                if ($resend && (! $pending || $pending->expires_at->lessThanOrEqualTo(now()) || $pending->old_email !== $lockedUser->email || ! hash_equals($pending->session_hash, hash('sha256', $sessionToken)))) {
                    throw ValidationException::withMessages(['new_email' => __('Sesi perubahan email sudah berakhir. Ajukan perubahan email kembali.')]);
                }
                if ($pending && $pending->sent_at->greaterThan(now()->subSeconds(60))) {
                    throw ValidationException::withMessages(['new_email' => __('Tunggu 60 detik sebelum mengirim kode baru.')]);
                }
                $target = $resend ? $pending->new_email : $newEmail;
                if (User::whereRaw('LOWER(email) = ?', [$target])->exists()) {
                    throw ValidationException::withMessages(['new_email' => __('Email baru sudah digunakan oleh akun lain.')]);
                }
                $code = (string) random_int(100000, 999999);
                EmailChange::updateOrCreate(['user_id' => $lockedUser->id], [
                    'old_email' => $lockedUser->email, 'new_email' => $target,
                    'code_hash' => Hash::make($code), 'session_hash' => hash('sha256', $sessionToken),
                    'attempts' => 0, 'sent_at' => now(), 'expires_at' => now()->addMinutes(self::LIFETIME_MINUTES),
                ]);
                Mail::to($target)->locale(app()->getLocale())->send(new EmailChangeCode($code));
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::warning('Email change verification delivery failed.', ['exception_class' => $exception::class]);
            throw ValidationException::withMessages(['new_email' => __('Kode belum berhasil dikirim. Silakan coba lagi nanti.')]);
        }
    }

    public function verify(User $user, string $code, string $sessionToken, string $sessionId): void
    {
        try {
            $error = DB::transaction(function () use ($user, $code, $sessionToken, $sessionId): ?string {
                $lockedUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                $pending = EmailChange::where('user_id', $user->id)->lockForUpdate()->first();
                if (! $pending || ! hash_equals($pending->session_hash, hash('sha256', $sessionToken))) {
                    return __('Sesi perubahan email sudah berakhir. Ajukan perubahan email kembali.');
                }
                if ($pending->expires_at->lessThanOrEqualTo(now()) || $pending->old_email !== $lockedUser->email) {
                    $pending->delete();

                    return __('Kode sudah kedaluwarsa. Kirim kode baru.');
                }
                if ($pending->attempts >= self::MAX_ATTEMPTS) {
                    return __('Batas percobaan tercapai. Kirim kode baru.');
                }
                $pending->increment('attempts');
                if (! Hash::check($code, $pending->code_hash)) {
                    return $pending->attempts >= self::MAX_ATTEMPTS ? __('Batas percobaan tercapai. Kirim kode baru.') : __('Kode verifikasi tidak sesuai.');
                }
                if (User::where('id', '!=', $lockedUser->id)->whereRaw('LOWER(email) = ?', [$pending->new_email])->exists()) {
                    $pending->delete();

                    return __('Email baru sudah digunakan oleh akun lain.');
                }
                $oldEmail = $lockedUser->email;
                $lockedUser->forceFill(['email' => $pending->new_email, 'email_verified_at' => now(), 'google_id' => null, 'remember_token' => Str::random(60)])->save();
                DB::table('password_reset_tokens')->whereIn('email', [$oldEmail, $pending->new_email])->delete();
                if (config('session.driver') === 'database') {
                    DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->where('id', '!=', $sessionId)->delete();
                }
                $pending->delete();

                return null;
            });
        } catch (UniqueConstraintViolationException) {
            $error = __('Email baru sudah digunakan oleh akun lain.');
        }
        if ($error !== null) {
            throw ValidationException::withMessages(['verification_code' => $error]);
        }
    }
}
