<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class Bill extends Model
{
    public const STATUS_LABELS = ['unpaid' => 'Belum lunas', 'paid' => 'Lunas', 'cancelled' => 'Dibatalkan'];

    public const PAYMENT_TYPE_LABELS = ['full' => 'Lunas (tanpa DP)', 'dp' => 'DP (bayar sebagian)'];

    protected $fillable = ['user_id', 'service_id', 'member_tracking_id', 'reference', 'title', 'quantity', 'unit_price', 'discount', 'total', 'due_date', 'customer_name', 'customer_email', 'customer_number', 'service_name', 'issuer_name', 'issuer_email', 'issued_by', 'items', 'payment_type', 'dp_amount', 'dp_paid_at'];

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
        return ['items' => 'array', 'quantity' => 'integer', 'unit_price' => 'integer', 'discount' => 'integer', 'total' => 'integer', 'due_date' => 'immutable_date', 'paid_at' => 'immutable_datetime', 'dp_amount' => 'integer', 'dp_paid_at' => 'immutable_datetime'];
    }

    /** @return list<array{name: string, quantity: int, unit_price: int}> */
    public function lineItems(): array
    {
        return $this->items ?: [[
            'name' => $this->service_name ?? $this->service?->name ?? $this->title,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
        ]];
    }

    public function subtotal(): int
    {
        return array_sum(array_map(fn (array $item): int => $item['quantity'] * $item['unit_price'], $this->lineItems()));
    }

    public function isPartiallyPaid(): bool
    {
        return $this->status === 'unpaid' && $this->dp_amount > 0 && $this->dp_amount < $this->total;
    }

    public function paidAmount(): int
    {
        return $this->status === 'paid' ? $this->total : min($this->dp_amount, $this->total);
    }

    public function remainingAmount(): int
    {
        return $this->status === 'cancelled' ? 0 : max(0, $this->total - $this->paidAmount());
    }

    public function statusLabel(): string
    {
        return $this->isPartiallyPaid() ? 'DP / Sebagian' : self::STATUS_LABELS[$this->status];
    }

    public function statusTone(): string
    {
        return $this->isPartiallyPaid() ? 'dp' : $this->status;
    }

    public function paymentTypeLabel(): string
    {
        return self::PAYMENT_TYPE_LABELS[$this->payment_type] ?? self::PAYMENT_TYPE_LABELS['full'];
    }

    public function downPaymentPercentage(): float
    {
        return $this->total > 0 ? round($this->dp_amount / $this->total * 100, 1) : 0.0;
    }

    public function downPaymentPercentageLabel(): string
    {
        $percentage = $this->downPaymentPercentage();

        return number_format($percentage, $percentage === floor($percentage) ? 0 : 1, ',', '.');
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
