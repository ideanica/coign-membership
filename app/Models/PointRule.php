<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointRule extends Model
{
    protected $fillable = ['name', 'minimum_amount', 'points', 'is_active'];

    protected function casts(): array
    {
        return ['minimum_amount' => 'integer', 'points' => 'integer', 'is_active' => 'boolean'];
    }
}
