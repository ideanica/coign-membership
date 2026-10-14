<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MembershipSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function edit()
    {
        return view('admin.settings', ['settings' => app(MembershipSettings::class)->all()]);
    }

    public function update(Request $request)
    {
        $values = $request->validate(['brand_name' => ['required', 'string', 'max:40'], 'support_email' => ['nullable', 'email', 'max:254'], 'support_x' => ['sometimes', 'nullable', 'string', 'max:16', 'regex:/^@?[A-Za-z0-9_]{1,15}$/'], 'support_whatsapp' => ['sometimes', 'nullable', 'string', 'regex:/^\+?[1-9][0-9]{7,14}$/'], 'rupiah_per_point' => ['required', 'integer', 'min:1', 'max:1000000000']]);
        DB::transaction(function () use ($values): void {
            foreach ($values as $key => $value) {
                DB::table('site_settings')->updateOrInsert(['key' => $key], ['value' => (string) ($value ?? ''), 'updated_at' => now()]);
            }
        });
        Cache::forget('membership.public-settings');

        return redirect()->route('admin.settings.edit')->with('success', 'Pengaturan disimpan. Rasio poin baru berlaku untuk transaksi berikutnya.');
    }
}
