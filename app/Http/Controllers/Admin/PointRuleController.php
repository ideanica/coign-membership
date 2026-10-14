<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PointRule;
use App\Models\User;
use App\Services\MembershipLedger;
use App\Services\MembershipSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PointRuleController extends Controller
{
    public function index()
    {
        return view('admin.point-rules', ['rules' => PointRule::orderBy('minimum_amount')->get(), 'members' => User::where('is_admin', false)->where('is_active', true)->orderBy('name')->get(), 'settings' => app(MembershipSettings::class)->all(), 'purchaseReference' => 'PUR-'.Str::ulid()]);
    }

    private function values(Request $request, ?PointRule $pointRule = null): array
    {
        $values = $request->validate(['name' => ['required', 'string', 'max:100'], 'minimum_amount' => ['required', 'integer', 'min:1', 'max:10000000000000', Rule::unique('point_rules')->ignore($pointRule?->id)], 'points' => ['required', 'integer', 'min:1', 'max:100000000'], 'is_active' => ['sometimes', 'boolean']]);

        return [...$values, 'is_active' => $request->boolean('is_active')];
    }

    public function store(Request $request)
    {
        PointRule::create($this->values($request));

        return back()->with('success', 'Aturan poin ditambahkan.');
    }

    public function update(Request $request, PointRule $pointRule)
    {
        $pointRule->update($this->values($request, $pointRule));

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
