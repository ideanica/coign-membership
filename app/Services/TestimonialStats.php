<?php

namespace App\Services;

use App\Models\Catalog;
use App\Models\Testimonial;

class TestimonialStats
{
    public function forCatalog(Catalog $catalog): array
    {
        $counts = Testimonial::where('catalog_id', $catalog->id)->where('is_visible', true)->selectRaw('rating, COUNT(*) as total')->groupBy('rating')->pluck('total', 'rating');
        $total = (int) $counts->sum();
        $sum = $counts->reduce(fn ($sum, $count, $rating) => $sum + $rating * $count, 0);
        $distribution = [];
        foreach (range(5, 1) as $rating) {
            $count = (int) ($counts[$rating] ?? 0);
            $distribution[$rating] = ['count' => $count, 'percent' => $total ? round(100 * $count / $total) : 0];
        }

        return ['total' => $total, 'average' => $total ? round($sum / $total, 2) : 0, 'distribution' => $distribution];
    }

    public function sync(Catalog $catalog): void
    {
        $stats = $this->forCatalog($catalog);
        $catalog->update(['rating_average' => $stats['average'], 'reviews_count' => $stats['total']]);
    }
}
