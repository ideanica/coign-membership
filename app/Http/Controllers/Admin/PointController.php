<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PointEntry;
use App\Models\User;
use App\Services\MembershipLedger;
use App\Services\MembershipSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PointController extends Controller
{
    public function index(Request $request): View
    {
        $entries = PointEntry::with(['user', 'actor'])->latest('occurred_at')->latest('id');
        if ($request->filled('member')) {
            $entries->where('user_id', $request->integer('member'));
        }
        $summary = ['earned' => (int) (clone $entries)->where('type', 'earned')->sum('points'), 'used' => (int) (clone $entries)->where('type', 'redeemed')->sum('points')];
        if (in_array($request->query('type'), ['earned', 'redeemed'], true)) {
            $entries->where('type', $request->query('type'));
        }
        if ($request->filled('q')) {
            $search = '%'.mb_substr((string) $request->query('q'), 0, 100).'%';
            $entries->where(fn ($query) => $query->whereLike('reference', $search)->orWhereLike('title', $search)->orWhereLike('description', $search)->orWhereHas('user', fn ($member) => $member->whereLike('name', $search)));
        }

        return view('admin.points', ['members' => User::where('is_admin', false)->withPointTotals()->orderBy('name')->get(), 'entries' => $entries->paginate(15)->withQueryString(), 'summary' => $summary, 'settings' => app(MembershipSettings::class)->all()]);
    }

    public function store(Request $request, MembershipLedger $ledger)
    {
        $values = $request->validate(['user_id' => ['bail', 'required', 'integer', Rule::exists('users', 'id')->where('is_admin', false)->where('is_active', true)], 'type' => ['required', Rule::in(['earned', 'redeemed'])], 'points' => ['required', 'integer', 'min:1', 'max:100000000'], 'reason' => ['required', 'string', 'max:2000']]);
        $member = User::findOrFail($values['user_id']);
        $attributes = ['reference' => 'ADJ-'.Str::ulid(), 'title' => $values['type'] === 'earned' ? 'Penambahan poin admin' : 'Pengurangan poin admin', 'category' => 'Penyesuaian', 'points' => $values['points'], 'description' => $values['reason'], 'occurred_at' => now()];
        if ($values['type'] === 'earned') {
            $ledger->credit($member, $attributes, $request->user());
        } else {
            $ledger->redeem($member, $attributes, $request->user());
        }

        return redirect()->route('admin.points.index', ['member' => $member->id])->with('success', 'Perubahan poin berhasil dicatat.');
    }
}
