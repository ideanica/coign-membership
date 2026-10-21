<?php

namespace App\Http\Controllers;

use App\Models\Catalog;
use App\Models\Testimonial;
use App\Services\TestimonialStats;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TestimonialController extends Controller
{
    public function store(Request $request, Catalog $catalog, TestimonialStats $stats)
    {
        abort_unless($catalog->is_active, 404);
        abort_if($request->user()?->is_admin || ($request->user() && ! $request->user()->is_active), 403);
        foreach (['reviewer_name', 'comment'] as $field) {
            if (is_string($request->input($field))) {
                $request->merge([$field => trim($request->input($field))]);
            }
        }
        $values = $request->validate([
            'reviewer_name' => [Rule::requiredIf(! $request->user()), 'nullable', 'string', 'min:2', 'max:80'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:5', 'max:2000'],
            'website' => ['nullable', 'max:0'],
        ]);
        DB::transaction(function () use ($request, $catalog, $stats, $values): void {
            $locked = Catalog::whereKey($catalog->id)->lockForUpdate()->firstOrFail();
            $review = $request->user()
                ? Testimonial::firstOrNew(['catalog_id' => $locked->id, 'user_id' => $request->user()->id])
                : new Testimonial(['catalog_id' => $locked->id]);
            $review->fill(['reviewer_name' => mb_substr(trim($request->user()?->name ?? $values['reviewer_name']), 0, 80), 'rating' => $values['rating'], 'comment' => trim($values['comment']), 'is_visible' => false, 'moderated_by' => null, 'moderated_at' => null])->save();
            $stats->sync($locked);
        });

        return redirect()->to(route('catalog.show', $catalog->slug).'#testimonials')->with('testimonial_success', __('Terima kasih! Testimonimu tersimpan dan akan tampil setelah ditinjau admin.'));
    }
}
