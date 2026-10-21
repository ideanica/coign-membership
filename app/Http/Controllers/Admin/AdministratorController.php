<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MembershipSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdministratorController extends Controller
{
    public function index(Request $request): View
    {
        $values = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'access' => ['nullable', Rule::in(['active', 'inactive'])]]);
        $query = User::where('is_admin', true)->where('is_superadmin', false)->latest();
        if (filled($values['q'] ?? null)) {
            $search = '%'.$values['q'].'%';
            $query->where(fn ($builder) => $builder->whereLike('name', $search)->orWhereLike('email', $search));
        }
        if (filled($values['access'] ?? null)) {
            $query->where('is_active', $values['access'] === 'active');
        }

        return view('admin.administrators.index', [
            'administrators' => $query->paginate(15)->withQueryString(),
            'settings' => app(MembershipSettings::class)->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => Str::lower(trim($request->input('email')))]);
        }
        $values = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:254', Rule::unique('users')],
            'password' => ['required', 'string', 'min:12', 'max:255'],
        ]);
        DB::transaction(function () use ($values): void {
            $administrator = User::create($values);
            $administrator->forceFill([
                'is_admin' => true, 'is_superadmin' => false, 'is_active' => true, 'password_setup_at' => now(),
            ])->save();
        });

        return redirect()->route('admin.administrators.index')->with('success', 'Admin berhasil ditambahkan dan dapat masuk menggunakan email serta kata sandinya.');
    }

    public function access(Request $request, User $administrator): RedirectResponse
    {
        abort_unless($administrator->is_admin && ! $administrator->is_superadmin, 404);
        $values = $request->validate(['is_active' => ['required', 'boolean']]);
        DB::transaction(function () use ($administrator, $values): void {
            $locked = User::whereKey($administrator->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->is_admin && ! $locked->is_superadmin, 404);
            $locked->forceFill(['is_active' => $values['is_active']])->save();
        });

        return redirect()->route('admin.administrators.index')->with('success', $values['is_active'] ? 'Akses admin diaktifkan kembali.' : 'Akses admin dinonaktifkan. Riwayat pekerjaannya tetap tersimpan.');
    }
}
