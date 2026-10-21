<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    private const AUTHORIZE_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const USERINFO_URL = 'https://openidconnect.googleapis.com/v1/userinfo';

    public function redirect(Request $request): RedirectResponse
    {
        if (! $this->isConfigured()) {
            return redirect()->route('login')->withErrors([
                'google' => __('Login Google belum dikonfigurasi oleh pengelola.'),
            ]);
        }

        $state = Str::random(64);
        $codeVerifier = Str::random(96);
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        $request->session()->put([
            'google_oauth_state' => $state,
            'google_oauth_code_verifier' => $codeVerifier,
        ]);

        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'prompt' => 'select_account',
        ], '', '&', PHP_QUERY_RFC3986);

        return redirect()->away(self::AUTHORIZE_URL.'?'.$query);
    }

    public function callback(Request $request): RedirectResponse
    {
        $expectedState = (string) $request->session()->pull('google_oauth_state', '');
        $codeVerifier = (string) $request->session()->pull('google_oauth_code_verifier', '');

        if ($request->filled('error')) {
            return redirect()->route('login')->withErrors([
                'google' => __('Login Google dibatalkan atau tidak dapat diselesaikan.'),
            ]);
        }

        $state = (string) $request->query('state', '');
        $code = (string) $request->query('code', '');

        if (
            $expectedState === ''
            || $codeVerifier === ''
            || $state === ''
            || $code === ''
            || ! hash_equals($expectedState, $state)
        ) {
            return redirect()->route('login')->withErrors([
                'google' => __('Sesi login Google tidak valid. Silakan coba lagi.'),
            ]);
        }

        if (! $this->isConfigured()) {
            return redirect()->route('login')->withErrors([
                'google' => __('Login Google belum dikonfigurasi oleh pengelola.'),
            ]);
        }

        try {
            $tokenPayload = Http::asForm()
                ->connectTimeout(3)
                ->timeout(10)
                ->post(self::TOKEN_URL, [
                    'client_id' => config('services.google.client_id'),
                    'client_secret' => config('services.google.client_secret'),
                    'code' => $code,
                    'code_verifier' => $codeVerifier,
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => $this->redirectUri(),
                ])
                ->throw()
                ->json();

            $accessToken = is_array($tokenPayload) ? ($tokenPayload['access_token'] ?? null) : null;

            if (! is_string($accessToken) || $accessToken === '') {
                return redirect()->route('login')->withErrors([
                    'google' => __('Google tidak mengembalikan sesi login yang valid.'),
                ]);
            }

            $profile = Http::withToken($accessToken)
                ->acceptJson()
                ->connectTimeout(3)
                ->timeout(10)
                ->get(self::USERINFO_URL)
                ->throw()
                ->json();
        } catch (ConnectionException|RequestException) {
            return redirect()->route('login')->withErrors([
                'google' => __('Google sedang tidak dapat dihubungi. Silakan coba lagi.'),
            ]);
        }

        $googleId = is_array($profile) ? ($profile['sub'] ?? null) : null;
        $googleEmail = is_array($profile) ? ($profile['email'] ?? null) : null;
        $emailVerified = is_array($profile) ? ($profile['email_verified'] ?? false) : false;

        if (
            ! is_string($googleId)
            || $googleId === ''
            || ! is_string($googleEmail)
            || $googleEmail === ''
            || $emailVerified !== true
        ) {
            return redirect()->route('login')->withErrors([
                'google' => __('Akun Google tidak memiliki email terverifikasi yang dapat digunakan.'),
            ]);
        }

        $email = Str::lower(trim($googleEmail));
        $user = User::where('google_id', $googleId)->first();

        if (! $user) {
            $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

            if (! $user) {
                return redirect()->route('login')->withErrors([
                    'google' => __('Email Google ini belum terdaftar sebagai member. Hubungi pengelola untuk mendapatkan akses.'),
                ]);
            }

            if (filled($user->google_id) && ! hash_equals((string) $user->google_id, $googleId)) {
                return redirect()->route('login')->withErrors([
                    'google' => __('Email member ini sudah terhubung ke akun Google lain.'),
                ]);
            }

            $user->forceFill([
                'google_id' => $googleId,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        }

        if (! $user->is_active) {
            return redirect()->route('login')->withErrors([
                'google' => __('Akun dinonaktifkan. Hubungi pengelola membership.'),
            ]);
        }

        if (! $user->is_admin && $user->password_setup_at === null) {
            $user->forceFill(['password' => Str::password(64)])->save();
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('auth_via_google', true);

        return redirect()->intended(route($user->is_admin ? 'admin.dashboard' : 'membership.profile'));
    }

    private function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }

    private function redirectUri(): string
    {
        return (string) (config('services.google.redirect') ?: route('auth.google.callback'));
    }
}
