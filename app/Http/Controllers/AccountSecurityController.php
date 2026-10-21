<?php

namespace App\Http\Controllers;

use App\Models\EmailChange;
use App\Services\MembershipSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountSecurityController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        if ($request->user()->is_admin) {
            return redirect()->route('admin.settings.edit');
        }

        return redirect()->route('membership.profile', ['settings' => 1]);
    }

    public function setup(Request $request): View|RedirectResponse
    {
        if ($request->user()->is_admin) {
            return redirect()->route('admin.settings.edit');
        }

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

        if ($user->is_admin || ! $wasSetupRequired) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $values = $request->validate($rules, [
            'current_password.current_password' => __('Kata sandi saat ini tidak sesuai.'),
            'password.confirmed' => __('Konfirmasi kata sandi baru tidak sesuai.'),
        ]);

        DB::transaction(function () use ($user, $values): void {
            $user->forceFill(['password' => $values['password'], 'password_setup_at' => now()])->save();
            EmailChange::where('user_id', $user->id)->delete();
        });

        $request->session()->forget(['auth_via_google', 'email_change_token']);
        $request->session()->regenerate();

        if ($user->is_admin) {
            return redirect()->route('admin.settings.edit')->with('success', __('Kata sandi berhasil diperbarui.'));
        }

        if ($wasSetupRequired) {
            return redirect()->route('membership.profile')->with('success', __('Kata sandi baru berhasil dibuat.'));
        }

        return redirect()->route('membership.profile', ['settings' => 1])->with('success', __('Kata sandi berhasil diperbarui.'));
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
