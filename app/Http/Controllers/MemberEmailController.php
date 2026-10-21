<?php

namespace App\Http\Controllers;

use App\Services\EmailChangeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MemberEmailController extends Controller
{
    public function store(Request $request, EmailChangeService $service): RedirectResponse
    {
        if (is_string($request->input('new_email'))) {
            $request->merge(['new_email' => Str::lower(trim($request->input('new_email')))]);
        }
        $values = $request->validate([
            'new_email' => ['bail', 'required', 'string', 'email', 'max:254', Rule::unique('users', 'email')],
            'current_password' => ['bail', Rule::requiredIf($request->user()->password_setup_at !== null || ! $request->session()->get('auth_via_google', false)), 'nullable', 'string', 'current_password'],
        ]);
        $token = (string) $request->session()->get('email_change_token', Str::random(64));
        $request->session()->put('email_change_token', $token);
        $service->send($request->user(), $values['new_email'], $token);

        return redirect()->route('membership.profile', ['settings' => 1])->with('success', __('Kode verifikasi dikirim ke email baru. Email akun belum berubah.'));
    }

    public function resend(Request $request, EmailChangeService $service): RedirectResponse
    {
        $service->send($request->user(), '', (string) $request->session()->get('email_change_token', ''), true);

        return redirect()->route('membership.profile', ['settings' => 1])->with('success', __('Kode baru sudah dikirim. Kode sebelumnya tidak berlaku.'));
    }

    public function update(Request $request, EmailChangeService $service): RedirectResponse
    {
        $values = $request->validate(['verification_code' => ['bail', 'required', 'string', 'digits:6']]);
        $service->verify($request->user(), $values['verification_code'], (string) $request->session()->get('email_change_token', ''), $request->session()->getId());
        $request->user()->refresh();
        $request->session()->forget(['auth_via_google', 'email_change_token']);
        $request->session()->regenerate();
        $route = $request->user()->password_setup_at === null ? 'membership.account.setup' : 'membership.profile';

        return redirect()->route($route, $route === 'membership.profile' ? ['settings' => 1] : [])->with('success', __('Email akun berhasil diverifikasi dan diperbarui. Gunakan email baru untuk masuk berikutnya.'));
    }
}
