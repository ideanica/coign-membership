<?php

namespace App\Http\Controllers;

use App\Services\MembershipSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MemberAuthController extends Controller
{
    public function loginForm(): View
    {
        return view('membership.auth', [
            'settings' => app(MembershipSettings::class)->all(),
            'googleLoginEnabled' => filled(config('services.google.client_id')) && filled(config('services.google.client_secret')),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:254'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([...$credentials, 'is_active' => true], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => __('Email atau kata sandi tidak sesuai.')]);
        }

        $request->session()->regenerate();
        $request->session()->put('auth_via_google', false);

        if (! $request->user()->is_admin && $request->user()->password_setup_at === null) {
            return redirect()->route('membership.account.setup');
        }

        return redirect()->intended(route($request->user()->is_admin ? 'admin.dashboard' : 'membership.profile'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
