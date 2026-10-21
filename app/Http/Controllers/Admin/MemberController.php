<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MembershipSettings;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::where('is_admin', false)->withPointTotals()->latest();
        if ($request->filled('q')) {
            $search = '%'.mb_substr((string) $request->query('q'), 0, 100).'%';
            $query->where(fn ($builder) => $builder->whereLike('name', $search)->orWhereLike('email', $search)->orWhereLike('member_number', $search));
        }
        if (in_array($request->query('access'), ['active', 'inactive'], true)) {
            $query->where('is_active', $request->query('access') === 'active');
        }

        $temporaryCredentials = null;
        $encryptedCredentials = $request->session()->pull('temporary_credentials');

        if (is_array($encryptedCredentials) && isset($encryptedCredentials['name'], $encryptedCredentials['email'], $encryptedCredentials['password'])) {
            try {
                $temporaryCredentials = [
                    'name' => (string) $encryptedCredentials['name'],
                    'email' => (string) $encryptedCredentials['email'],
                    'password' => Crypt::decryptString((string) $encryptedCredentials['password']),
                ];
            } catch (DecryptException) {
                $temporaryCredentials = null;
            }
        }

        return view('admin.members.index', [
            'members' => $query->paginate(15)->withQueryString(),
            'memberStats' => ['total' => User::where('is_admin', false)->count(), 'active' => User::where('is_admin', false)->where('is_active', true)->count(), 'inactive' => User::where('is_admin', false)->where('is_active', false)->count()],
            'editingMember' => old('_workspace_form') === 'member-edit' ? (User::where('is_admin', false)->find((int) old('_workspace_id')) ?? new User) : new User,
            'newMember' => new User,
            'temporaryCredentials' => $temporaryCredentials,
            'settings' => app(MembershipSettings::class)->all(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new User);
    }

    public function edit(User $member): View
    {
        abort_if($member->is_admin, 404);

        return $this->form($member);
    }

    private function form(User $member): View
    {
        return view('admin.members.form', ['member' => $member, 'settings' => app(MembershipSettings::class)->all()]);
    }

    private function values(Request $request, ?User $member = null): array
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:254', Rule::unique('users')->ignore($member?->id)],
            'member_tier' => ['required', 'string', 'max:64'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $values = $this->values($request);
        $temporaryPassword = Str::password(16);

        $member = DB::transaction(function () use ($values, $temporaryPassword): User {
            $member = User::create([
                'name' => $values['name'],
                'email' => $values['email'],
                'password' => $temporaryPassword,
            ]);
            $member->forceFill([
                'member_number' => 'CG-'.now()->format('Y').'-'.str_pad((string) $member->id, 5, '0', STR_PAD_LEFT),
                'member_tier' => $values['member_tier'],
                'is_active' => $values['is_active'],
                'password_setup_at' => null,
            ])->save();

            return $member;
        });

        return redirect()->route('admin.members.index')
            ->with('success', 'Akun member berhasil dibuat. Salin kredensial sementara sebelum meninggalkan halaman ini.')
            ->with('temporary_credentials', [
                'name' => $member->name,
                'email' => $member->email,
                'password' => Crypt::encryptString($temporaryPassword),
            ]);
    }

    public function update(Request $request, User $member): RedirectResponse
    {
        abort_if($member->is_admin, 404);
        $values = $this->values($request, $member);
        $emailChanged = $member->email !== $values['email'];

        if ($emailChanged) {
            $values['google_id'] = null;
            $values['email_verified_at'] = null;
        }

        $member->forceFill($values)->save();

        return redirect()->route('admin.members.index')->with('success', $emailChanged
            ? 'Data member diperbarui. Tautan Google dilepas karena email member berubah.'
            : 'Data member diperbarui.');
    }

    public function destroy(User $member): RedirectResponse
    {
        abort_if($member->is_admin, 404);
        $member->forceFill(['is_active' => false])->save();

        return back()->with('success', 'Member dinonaktifkan. Riwayat tetap tersimpan.');
    }
}
