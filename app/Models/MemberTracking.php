<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class MemberTracking extends Model
{
    protected $fillable = ['user_id', 'service_id', 'tracking_status_id', 'reference', 'title', 'description', 'due_date', 'closed_at', 'public_enabled'];

    protected static function booted(): void
    {
        static::creating(function (MemberTracking $tracking): void {
            $tracking->public_code = 'ORD-'.strtoupper(Str::random(16));
        });
    }

    protected function casts(): array
    {
        return ['due_date' => 'immutable_date', 'closed_at' => 'immutable_datetime', 'public_enabled' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TrackingStatus::class, 'tracking_status_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(TrackingEvent::class)->orderByDesc('id');
    }

    public function bill(): HasOne
    {
        return $this->hasOne(Bill::class);
    }

    public function latestEvent(): HasOne
    {
        return $this->hasOne(TrackingEvent::class)->latestOfMany();
    }
}
