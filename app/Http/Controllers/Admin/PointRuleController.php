<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PointRule;
use App\Models\User;
use App\Services\MembershipLedger;
use App\Services\MembershipSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PointRuleController extends Controller
{
    public function index(): View
    {
        return view('admin.point-rules', ['rules' => PointRule::orderBy('minimum_amount')->get(), 'members' => User::where('is_admin', false)->where('is_active', true)->orderBy('name')->get(), 'settings' => app(MembershipSettings::class)->all(), 'purchaseReference' => 'PUR-'.Str::ulid(), 'newRule' => new PointRule, 'editingRule' => old('_workspace_form') === 'rule-edit' ? (PointRule::find((int) old('_workspace_id')) ?? new PointRule) : new PointRule]);
    }

    private function values(Request $request, ?PointRule $pointRule = null): array
    {
        $request->merge([
            'reward_type' => $request->input('reward_type', $pointRule?->reward_type ?? 'fixed'),
            'maximum_amount' => $request->input('maximum_amount', $pointRule?->maximum_amount),
        ]);
        $values = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'minimum_amount' => ['required', 'integer', 'min:1', 'max:10000000000000', Rule::unique('point_rules')->ignore($pointRule?->id)],
            'maximum_amount' => ['nullable', 'integer', 'gte:minimum_amount', 'max:10000000000000'],
            'reward_type' => ['required', Rule::in(['fixed', 'multiple'])],
            'points' => ['required', 'integer', 'min:1', 'max:'.PointRule::MAX_POINTS],
            'step_amount' => ['exclude_unless:reward_type,multiple', 'required', 'integer', 'min:1', 'max:10000000000000'],
            'maximum_points' => ['exclude_unless:reward_type,multiple', 'nullable', 'integer', 'min:1', 'max:'.PointRule::MAX_POINTS],
            'is_active' => ['sometimes', 'boolean'],
        ], ['maximum_amount.gte' => 'Batas maksimum harus sama dengan atau lebih besar dari batas minimum.']);
        if ($request->boolean('is_active')) {
            $overlap = PointRule::where('is_active', true)
                ->when($pointRule, fn ($query) => $query->where('id', '!=', $pointRule->id))
                ->where(fn ($query) => $query->whereNull('maximum_amount')->orWhere('maximum_amount', '>=', $values['minimum_amount']))
                ->when($values['maximum_amount'] !== null, fn ($query) => $query->where('minimum_amount', '<=', $values['maximum_amount']))
                ->first();
            if ($overlap) {
                throw ValidationException::withMessages(['minimum_amount' => 'Rentang bertabrakan dengan aturan aktif "'.$overlap->name.'". Sesuaikan batas minimum/maksimum atau nonaktifkan salah satu aturan.']);
            }
        }

        return [...$values, 'step_amount' => $values['step_amount'] ?? null, 'maximum_points' => $values['maximum_points'] ?? null, 'is_active' => $request->boolean('is_active')];
    }

    public function store(Request $request)
    {
        DB::transaction(function () use ($request): void {
            DB::table('site_settings')->where('key', 'points_mode')->lockForUpdate()->firstOrFail();
            PointRule::create($this->values($request));
        });

        return back()->with('success', 'Aturan poin ditambahkan.');
    }

    public function update(Request $request, PointRule $pointRule)
    {
        DB::transaction(function () use ($request, $pointRule): void {
            DB::table('site_settings')->where('key', 'points_mode')->lockForUpdate()->firstOrFail();
            $locked = PointRule::whereKey($pointRule->id)->lockForUpdate()->firstOrFail();
            $locked->update($this->values($request, $locked));
        });

        return back()->with('success', 'Aturan poin diperbarui. Riwayat poin sebelumnya tidak berubah.');
    }

    public function destroy(PointRule $pointRule)
    {
        $pointRule->delete();

        return back()->with('success', 'Aturan dihapus. Poin yang sudah masuk tetap tersimpan.');
    }

    public function mode(Request $request)
    {
        $values = $request->validate(['points_mode' => ['required', Rule::in(['ratio', 'tiers'])]]);
        DB::table('site_settings')->updateOrInsert(['key' => 'points_mode'], ['value' => $values['points_mode'], 'updated_at' => now()]);
        Cache::forget('membership.public-settings');

        return back()->with('success', 'Mode perolehan poin disimpan untuk transaksi berikutnya.');
    }

    public function purchase(Request $request, MembershipLedger $ledger)
    {
        $values = $request->validate([
            'user_id' => ['bail', 'required', 'integer', Rule::exists('users', 'id')->where('is_admin', false)->where('is_active', true)],
            'reference' => ['required', 'string', 'max:64', 'unique:membership_transactions,reference', 'unique:bills,reference'],
            'title' => ['required', 'string', 'max:255'], 'category' => ['required', 'string', 'max:64'],
            'amount' => ['required', 'integer', 'min:1', 'max:10000000000000'],
            'payment' => ['required', 'string', 'max:100'],
        ]);
        $member = User::findOrFail($values['user_id']);
        unset($values['user_id']);
        $purchase = $ledger->recordPurchase($member, [...$values, 'occurred_at' => now()], $request->user());
        $points = $purchase->pointEntry?->points ?? 0;

        return redirect()->route('admin.point-rules.index')->with('success', 'Pembelian dicatat. +'.number_format($points, 0, ',', '.').' poin masuk ke akun '.$member->name.'.');
    }
}
