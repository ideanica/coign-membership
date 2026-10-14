<?php

namespace App\Http\Controllers;

use App\Models\Catalog;
use App\Models\CatalogCategory;
use App\Models\Testimonial;
use App\Services\CatalogPricing;
use App\Services\MembershipSettings;
use App\Services\TestimonialStats;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InvitationCatalogController extends Controller
{
    public function index(Request $request)
    {
        $values = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'category' => ['nullable', 'string', 'max:120'], 'sort' => ['nullable', Rule::in(['featured', 'latest', 'price_low', 'price_high'])], 'per_page' => ['nullable', 'integer', Rule::in([10, 12])]]);
        $query = Catalog::where('catalogs.is_active', true)->with(['categories', 'images', 'discount']);
        if (filled($values['q'] ?? null)) {
            $query->whereLike('catalogs.name', '%'.$values['q'].'%');
        }
        if (filled($values['category'] ?? null)) {
            $query->whereHas('categories', fn ($q) => $q->where('slug', $values['category'])->where('is_active', true));
        }
        if (in_array($values['sort'] ?? '', ['price_low', 'price_high'], true)) {
            $now = now();
            $query->leftJoin('catalog_discounts as promo', fn ($join) => $join->on('promo.catalog_id', '=', 'catalogs.id')->where('promo.is_active', true)->where(fn ($q) => $q->whereNull('promo.starts_at')->orWhere('promo.starts_at', '<=', $now))->where(fn ($q) => $q->whereNull('promo.ends_at')->orWhere('promo.ends_at', '>=', $now)));
            $amount = "CASE WHEN promo.type = 'percent' THEN ROUND(catalogs.base_price * promo.value / 100.0) ELSE COALESCE(promo.value, 0) END";
            $query->select('catalogs.*')->selectRaw("CASE WHEN ($amount) > catalogs.base_price THEN 0 ELSE catalogs.base_price - ($amount) END AS sale_price")->orderBy('sale_price', $values['sort'] === 'price_low' ? 'asc' : 'desc')->orderBy('catalogs.id');
        } elseif (($values['sort'] ?? 'featured') === 'latest') {
            $query->latest('catalogs.id');
        } else {
            $query->orderByDesc('catalogs.is_featured')->latest('catalogs.id');
        }

        $catalogs = $query->paginate($values['per_page'] ?? 12)->withQueryString();
        $pricing = app(CatalogPricing::class);
        if ($request->header('X-Catalog-Fragment') === 'results') {
            return response()->json([
                'html' => view('catalog.partials.results', compact('catalogs', 'pricing'))->render(),
                'total' => $catalogs->total(),
            ])->header('Cache-Control', 'no-store');
        }

        return view('catalog.index', ['catalogs' => $catalogs, 'categories' => CatalogCategory::where('is_active', true)->orderBy('sort_order')->get(), 'pricing' => $pricing, 'settings' => app(MembershipSettings::class)->all()]);
    }

    public function show(Catalog $catalog)
    {
        abort_unless($catalog->is_active, 404);

        return view('catalog.show', ['catalog' => $catalog->load(['categories', 'images', 'specifications', 'discount']), 'pricing' => app(CatalogPricing::class), 'settings' => app(MembershipSettings::class)->all(),
            'reviewStats' => app(TestimonialStats::class)->forCatalog($catalog),
            'testimonials' => Testimonial::where('catalog_id', $catalog->id)->where('is_visible', true)->latest()->paginate(6, ['*'], 'review_page')->fragment('testimonials'),
            'myTestimonial' => auth()->check() ? Testimonial::where('catalog_id', $catalog->id)->where('user_id', auth()->id())->first() : null,
        ]);
    }
}
