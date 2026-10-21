<?php

namespace App\Http\Controllers;

use App\Services\MembershipSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccountSecurityController extends Controller
{
    public function edit(Request $request): View
    {
        return $this->securityView($request, false);
    }

    public function setup(Request $request): View|RedirectResponse
    {
        if ($request->user()->password_setup_at !== null) {
            return redirect()->route('membership.account.security');
        }

        return $this->securityView($request, true);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        $wasSetupRequired = $user->password_setup_at === null;
        $rules = [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];

        if (! $wasSetupRequired) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $values = $request->validate($rules, [
            'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak sesuai.',
        ]);

        $user->forceFill([
            'password' => $values['password'],
            'password_setup_at' => now(),
        ])->save();

        $request->session()->forget('auth_via_google');
        $request->session()->regenerate();

        if ($wasSetupRequired) {
            return redirect()->route('membership.profile')->with('success', 'Kata sandi baru berhasil dibuat.');
        }

        return redirect()->route('membership.account.security')->with('success', 'Kata sandi berhasil diperbarui.');
    }

    private function securityView(Request $request, bool $forceSetup): View
    {
        return view('membership.security', [
            'user' => $request->user(),
            'forceSetup' => $forceSetup,
            'authenticatedViaGoogle' => (bool) $request->session()->get('auth_via_google', false),
            'settings' => app(MembershipSettings::class)->all(),
        ]);
    }
}
