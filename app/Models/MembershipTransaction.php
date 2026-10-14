<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MembershipTransaction extends Model
{
    protected $fillable = ['reference', 'title', 'category', 'amount', 'payment', 'occurred_at'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'occurred_at' => 'immutable_datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pointEntry(): HasOne
    {
        return $this->hasOne(PointEntry::class, 'transaction_id');
    }
}
