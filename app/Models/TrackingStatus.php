<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackingStatus extends Model
{
    public const COLORS = ['#5294ff' => 'Biru', '#facc00' => 'Kuning', '#05e17a' => 'Hijau', '#ff4d50' => 'Merah', '#7a83ff' => 'Ungu', '#ffffff' => 'Putih'];

    protected $fillable = ['label', 'color', 'sort_order', 'is_final', 'is_active'];

    protected function casts(): array
    {
        return ['is_final' => 'boolean', 'is_active' => 'boolean', 'sort_order' => 'integer'];
    }
}
