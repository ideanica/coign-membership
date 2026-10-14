<?php

namespace App\Services;

use App\Models\Catalog;

/** Adapted from invite-me v2 CatalogPricingService. */
class CatalogPricing
{
    public function discountAmount(Catalog $catalog): int
    {
        $discount = $catalog->discount;
        if (! $discount || ! $discount->is_active || $discount->starts_at?->isFuture() || $discount->ends_at?->isPast()) {
            return 0;
        }
        $amount = $discount->type === 'percent' ? (int) round($catalog->base_price * $discount->value / 100) : $discount->value;

        return max(0, min($catalog->base_price, $amount));
    }

    public function finalPrice(Catalog $catalog): int
    {
        return $catalog->base_price - $this->discountAmount($catalog);
    }

    public function format(int $amount): string
    {
        return 'Rp'.number_format($amount, 0, ',', '.');
    }
}
