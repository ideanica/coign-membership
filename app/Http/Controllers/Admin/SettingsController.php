<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MembershipSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings', ['settings' => app(MembershipSettings::class)->all()]);
    }

    public function profile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $values = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:254', Rule::unique('users')->ignore($user->id)],
        ]);
        if ($values['email'] !== $user->email) {
            $user->forceFill(['google_id' => null, 'email_verified_at' => null]);
            $request->session()->forget('auth_via_google');
        }
        $user->fill($values)->save();

        return redirect()->route('admin.settings.edit')->with('success', 'Profil akun disimpan.');
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $request->validate(['_settings_section' => ['sometimes', 'required', 'in:identity,contact,points']]);
        $section = $request->input('_settings_section');
        $rules = [
            'brand_name' => ['required', 'string', 'max:40'],
            'support_email' => ['nullable', 'email', 'max:254'],
            'support_x' => ['sometimes', 'nullable', 'string', 'max:16', 'regex:/^@?[A-Za-z0-9_]{1,15}$/'],
            'support_whatsapp' => ['sometimes', 'nullable', 'string', 'regex:/^\+?[1-9][0-9]{7,14}$/'],
            'rupiah_per_point' => [$request->user()->isSuperAdmin() ? 'required' : 'sometimes', 'integer', 'min:1', 'max:1000000000'],
        ];
        $fields = match ($section) {
            'identity' => ['brand_name'],
            'contact' => ['support_email', 'support_x', 'support_whatsapp'],
            'points' => ['rupiah_per_point'],
            default => array_keys($rules),
        };
        $values = $request->validate(array_intersect_key($rules, array_flip($fields)));
        DB::transaction(function () use ($values): void {
            foreach ($values as $key => $value) {
                DB::table('site_settings')->updateOrInsert(['key' => $key], ['value' => (string) ($value ?? ''), 'updated_at' => now()]);
            }
        });
        Cache::forget('membership.public-settings');

        $message = match ($section) {
            'identity' => 'Identitas brand disimpan.',
            'contact' => 'Kontak publik disimpan.',
            'points' => 'Rasio poin disimpan dan berlaku untuk transaksi berikutnya.',
            default => 'Pengaturan disimpan.',
        };

        return redirect()->route('admin.settings.edit')->with('success', $message);
    }
}
