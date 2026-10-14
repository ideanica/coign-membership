<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['name', 'code', 'description', 'price', 'delivery_days', 'is_active'];

    protected function casts(): array
    {
        return ['price' => 'integer', 'delivery_days' => 'integer', 'is_active' => 'boolean'];
    }
}
