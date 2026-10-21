<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'google_id'])]
class User extends Authenticatable
{
    protected $attributes = ['is_admin' => false, 'is_active' => true, 'member_tier' => 'Member'];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function membershipTransactions(): HasMany
    {
        return $this->hasMany(MembershipTransaction::class);
    }

    public function pointEntries(): HasMany
    {
        return $this->hasMany(PointEntry::class);
    }

    public function trackings(): HasMany
    {
        return $this->hasMany(MemberTracking::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function scopeWithPointTotals(Builder $query): Builder
    {
        return $query->withSum(['pointEntries as earned_points' => fn (Builder $entries) => $entries->where('type', 'earned')], 'points')
            ->withSum(['pointEntries as used_points' => fn (Builder $entries) => $entries->where('type', 'redeemed')], 'points');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            'email_verified_at' => 'datetime',
            'password_setup_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
