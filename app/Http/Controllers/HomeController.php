<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use App\Services\MembershipSettings;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(MembershipSettings $settings): View
    {
        $values = $settings->all();
        $published = Testimonial::where('is_visible', true)->whereHas('catalog', fn ($query) => $query->where('is_active', true));
        $summary = (clone $published)->selectRaw('COUNT(*) as total, COALESCE(AVG(rating), 0) as average, COUNT(DISTINCT catalog_id) as templates')->first();

        return view('home', ['settings' => $values, 'brand' => $values['brand_name'] ?? 'Coignitive', 'services' => config('home'),
            'testimonials' => (clone $published)->with('catalog')->latest()->take(6)->get(),
            'testimonialStats' => ['total' => (int) $summary->total, 'average' => (float) $summary->average, 'templates' => (int) $summary->templates],
        ]);
    }
}
