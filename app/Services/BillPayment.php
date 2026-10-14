<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BillPayment
{
    public function pay(Bill $bill, User $admin, string $payment): Bill
    {
        return DB::transaction(function () use ($bill, $admin, $payment): Bill {
            $locked = Bill::query()->with('user')->whereKey($bill->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'paid') {
                return $locked;
            }
            if ($locked->status !== 'unpaid') {
                throw ValidationException::withMessages(['bill' => 'Bill yang dibatalkan tidak dapat dibayar.']);
            }
            $transaction = $locked->user ? app(MembershipLedger::class)->recordPurchase($locked->user, [
                'reference' => $locked->reference,
                'title' => $locked->title,
                'category' => 'Layanan',
                'amount' => $locked->total,
                'payment' => $payment,
                'occurred_at' => now(),
            ], $admin) : null;
            $locked->forceFill(['status' => 'paid', 'paid_at' => now(), 'paid_by' => $admin->id, 'payment' => $payment, 'transaction_id' => $transaction?->id])->save();

            return $locked;
        });
    }

    public function cancel(Bill $bill): void
    {
        DB::transaction(function () use ($bill): void {
            $locked = Bill::query()->whereKey($bill->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'paid') {
                throw ValidationException::withMessages(['bill' => 'Bill lunas tidak dapat dibatalkan karena poin sudah dicatat.']);
            }
            $locked->forceFill(['status' => 'cancelled'])->save();
        });
    }
}
