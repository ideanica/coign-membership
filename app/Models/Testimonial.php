<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Testimonial extends Model
{
    protected $fillable = ['catalog_id', 'user_id', 'legacy_id', 'reviewer_name', 'rating', 'comment', 'is_visible', 'moderated_by', 'moderated_at'];

    protected function casts(): array
    {
        return ['rating' => 'integer', 'is_visible' => 'boolean', 'moderated_at' => 'immutable_datetime'];
    }

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(Catalog::class);
    }
}
