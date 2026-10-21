<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Catalog extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'slug', 'description', 'preview_url', 'base_price', 'estimated_days', 'is_active', 'is_featured', 'rating_average', 'reviews_count'];

    protected function casts(): array
    {
        return ['base_price' => 'integer', 'estimated_days' => 'integer', 'is_active' => 'boolean', 'is_featured' => 'boolean', 'rating_average' => 'decimal:2', 'reviews_count' => 'integer'];
    }

    public function isNew(): bool
    {
        return $this->created_at !== null
            && $this->created_at->lessThanOrEqualTo(now())
            && $this->created_at->copy()->addMonthNoOverflow()->greaterThan(now());
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(CatalogCategory::class, 'catalog_category');
    }

    public function discount(): HasOne
    {
        return $this->hasOne(CatalogDiscount::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(CatalogImage::class)->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id');
    }

    public function specifications(): HasMany
    {
        return $this->hasMany(CatalogSpecification::class)->orderBy('sort_order');
    }
}
