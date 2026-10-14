<?php

namespace App\Services;

use App\Models\MembershipTransaction;
use App\Models\PointEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MembershipLedger
{
    public function recordPurchase(User $user, array $attributes, ?User $actor = null): MembershipTransaction
    {
        $values = Validator::make($attributes, [
            'reference' => ['required', 'string', 'max:64'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:64'],
            'amount' => ['required', 'integer', 'min:1'],
            'payment' => ['required', 'string', 'max:100'],
            'occurred_at' => ['required', 'date'],
        ])->validate();

        return DB::transaction(function () use ($user, $values, $actor): MembershipTransaction {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $purchase = $user->membershipTransactions()->create($values);
            $award = app(AutomaticPoints::class)->award((int) $values['amount']);
            $points = $award['points'];
            if ($points > 0) {
                $user->pointEntries()->create([
                    'transaction_id' => $purchase->id,
                    'reference' => 'PTS-'.Str::ulid(),
                    'type' => 'earned',
                    'points' => $points,
                    'title' => $purchase->title,
                    'category' => $purchase->category,
                    'occurred_at' => $purchase->occurred_at,
                    'actor_id' => $actor?->id,
                    'description' => $award['description'],
                ]);
            }

            return $purchase;
        });
    }

    /** Lock the member before checking balance to prevent concurrent overspending. */
    public function redeem(User $user, array $attributes, ?User $actor = null): PointEntry
    {
        $values = Validator::make($attributes, [
            'reference' => ['required', 'string', 'max:64'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:64'],
            'points' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:2000'],
            'occurred_at' => ['required', 'date'],
        ])->validate();

        return DB::transaction(function () use ($user, $values, $actor): PointEntry {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $balance = (int) $user->pointEntries()->selectRaw("COALESCE(SUM(CASE WHEN type = 'earned' THEN points ELSE -points END), 0) AS balance")->value('balance');
            if ($balance < (int) $values['points']) {
                throw ValidationException::withMessages(['points' => 'Saldo poin tidak mencukupi.']);
            }

            return $user->pointEntries()->create([...$values, 'type' => 'redeemed', 'actor_id' => $actor?->id]);
        });
    }

    public function credit(User $user, array $attributes, User $actor): PointEntry
    {
        $values = Validator::make($attributes, [
            'reference' => ['required', 'string', 'max:64'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:64'],
            'points' => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string', 'max:2000'],
            'occurred_at' => ['required', 'date'],
        ])->validate();

        return DB::transaction(function () use ($user, $values, $actor): PointEntry {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            return $user->pointEntries()->create([...$values, 'type' => 'earned', 'actor_id' => $actor->id]);
        });
    }
}
