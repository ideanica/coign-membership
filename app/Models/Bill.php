<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class Bill extends Model
{
    public const STATUS_LABELS = ['unpaid' => 'Belum lunas', 'paid' => 'Lunas', 'cancelled' => 'Dibatalkan'];

    protected $fillable = ['user_id', 'service_id', 'member_tracking_id', 'reference', 'title', 'quantity', 'unit_price', 'discount', 'total', 'due_date', 'customer_name', 'customer_email', 'customer_number', 'service_name', 'issuer_name', 'issuer_email', 'issued_by'];

    protected static function booted(): void
    {
        static::updating(function (Bill $bill): void {
            if ($bill->isDirty($bill->getFillable())) {
                throw ValidationException::withMessages(['bill' => 'Rincian bill sudah diterbitkan dan tidak dapat diubah.']);
            }
        });
    }

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'unit_price' => 'integer', 'discount' => 'integer', 'total' => 'integer', 'due_date' => 'immutable_date', 'paid_at' => 'immutable_datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function tracking(): BelongsTo
    {
        return $this->belongsTo(MemberTracking::class, 'member_tracking_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(MembershipTransaction::class, 'transaction_id');
    }
}
