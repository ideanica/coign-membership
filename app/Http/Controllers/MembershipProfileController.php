<?php

namespace App\Http\Controllers;

use App\Models\MembershipTransaction;
use App\Models\PointEntry;
use App\Services\EmailChangeService;
use App\Services\MembershipSettings;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class MembershipProfileController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $transactions = $user->membershipTransactions()->with('pointEntry')->orderByDesc('occurred_at')->orderByDesc('id')->get()->map(fn (MembershipTransaction $transaction): array => [
            'id' => $transaction->reference,
            'date' => $transaction->occurred_at->format('Y-m-d'),
            'title' => $transaction->title,
            'category' => $transaction->category,
            'amount' => $transaction->amount,
            'points' => $transaction->pointEntry?->points ?? 0,
            'payment' => $transaction->payment,
        ])->all();
        $entries = $user->pointEntries()->with('transaction')->orderByDesc('occurred_at')->orderByDesc('id')->get();
        $mapEntry = fn (PointEntry $entry): array => [
            'id' => $entry->reference,
            'transaction_reference' => $entry->transaction?->reference,
            'date' => $entry->occurred_at->format('Y-m-d'),
            'title' => $entry->title,
            'category' => $entry->category,
            'points' => $entry->points,
            'amount' => $entry->transaction?->amount,
            'payment' => $entry->transaction?->payment,
            'description' => $entry->description,
        ];
        $earnings = $entries->where('type', 'earned')->map($mapEntry)->values()->all();
        $redemptions = $entries->where('type', 'redeemed')->map($mapEntry)->values()->all();
        $earned = array_sum(array_column($earnings, 'points'));
        $used = array_sum(array_column($redemptions, 'points'));
        $periods = collect([...$transactions, ...$earnings, ...$redemptions])->pluck('date')->map(fn (string $date): string => substr($date, 0, 7))->unique()->sortDesc()->mapWithKeys(fn (string $period): array => [$period => CarbonImmutable::parse($period.'-01')->locale(app()->getLocale())->translatedFormat('F Y')])->all();

        return view('membership.profile', [
            'emailChange' => app(EmailChangeService::class)->pendingFor($user, (string) $request->session()->get('email_change_token', '')),
            'emailDeliveryAvailable' => app(EmailChangeService::class)->deliveryAvailable(),
            'member' => [
                'name' => $user->name,
                'first_name' => explode(' ', trim($user->name))[0],
                'email' => $user->email,
                'number' => $user->member_number ?? 'CG-'.str_pad((string) $user->id, 5, '0', STR_PAD_LEFT),
                'tier' => $user->member_tier ?: 'Member',
                'joined' => $user->created_at->locale(app()->getLocale())->translatedFormat('d F Y'),
                'photo' => $user->photo_path ?: 'images/member-avatar.svg',
            ],
            'transactions' => $transactions,
            'redemptions' => $redemptions,
            'earnings' => $earnings,
            'periods' => $periods,
            'rupiahPerPoint' => app(MembershipSettings::class)->rupiahPerPoint(),
            'settings' => app(MembershipSettings::class)->all(),
            'summary' => [
                'earned' => $earned,
                'used' => $used,
                'balance' => $earned - $used,
            ],
        ]);
    }
}
