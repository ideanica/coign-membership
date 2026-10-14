<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MembershipSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('is_admin', false)->withPointTotals()->latest();
        if ($request->filled('q')) {
            $search = '%'.mb_substr((string) $request->query('q'), 0, 100).'%';
            $query->where(fn ($builder) => $builder->whereLike('name', $search)->orWhereLike('email', $search)->orWhereLike('member_number', $search));
        }

        return view('admin.members.index', ['members' => $query->paginate(15)->withQueryString(), 'settings' => app(MembershipSettings::class)->all()]);
    }

    public function create()
    {
        return $this->form(new User);
    }

    public function edit(User $member)
    {
        abort_if($member->is_admin, 404);

        return $this->form($member);
    }

    private function form(User $member)
    {
        return view('admin.members.form', ['member' => $member, 'settings' => app(MembershipSettings::class)->all()]);
    }

    private function values(Request $request, ?User $member = null): array
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        return $request->validate(['name' => ['required', 'string', 'max:100'], 'email' => ['required', 'email', 'max:254', Rule::unique('users')->ignore($member?->id)], 'password' => [$member ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'], 'member_tier' => ['required', 'string', 'max:64'], 'is_active' => ['required', 'boolean']]);
    }

    public function store(Request $request)
    {
        $values = $this->values($request);
        DB::transaction(function () use ($values): void {
            $member = User::create(collect($values)->only(['name', 'email', 'password'])->all());
            $member->forceFill(['member_number' => 'CG-'.now()->format('Y').'-'.str_pad((string) $member->id, 5, '0', STR_PAD_LEFT), 'member_tier' => $values['member_tier'], 'is_active' => $values['is_active']])->save();
        });

        return redirect()->route('admin.members.index')->with('success', 'Akun member berhasil dibuat. Pelanggan dapat login menggunakan email dan kata sandi yang ditentukan.');
    }

    public function update(Request $request, User $member)
    {
        abort_if($member->is_admin, 404);
        $values = $this->values($request, $member);
        if (blank($values['password'] ?? null)) {
            unset($values['password']);
        }
        $member->forceFill($values)->save();

        return redirect()->route('admin.members.index')->with('success', 'Data member diperbarui.');
    }

    public function destroy(User $member)
    {
        abort_if($member->is_admin, 404);
        $member->forceFill(['is_active' => false])->save();

        return back()->with('success', 'Member dinonaktifkan. Riwayat tetap tersimpan.');
    }
}
