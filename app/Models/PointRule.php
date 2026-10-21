<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointRule extends Model
{
    protected $fillable = ['name', 'minimum_amount', 'maximum_amount', 'points', 'is_active', 'reward_type', 'step_amount', 'maximum_points'];

    public const MAX_POINTS = 100000000;

    protected $attributes = ['reward_type' => 'fixed'];

    public function pointsFor(int $amount): int
    {
        if (! $this->is_active || $amount < $this->minimum_amount || ($this->maximum_amount !== null && $amount > $this->maximum_amount)) {
            return 0;
        }
        if ($this->reward_type !== 'multiple') {
            return $this->points;
        }
        $steps = intdiv($amount, max(1, $this->step_amount ?? 1));
        $cap = $this->maximum_points ?? self::MAX_POINTS;
        if ($steps > intdiv($cap, max(1, $this->points))) {
            return $cap;
        }

        return min($cap, $steps * $this->points);
    }

    protected function casts(): array
    {
        return ['minimum_amount' => 'integer', 'maximum_amount' => 'integer', 'step_amount' => 'integer', 'maximum_points' => 'integer', 'points' => 'integer', 'is_active' => 'boolean'];
    }
}
