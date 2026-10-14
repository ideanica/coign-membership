<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Catalog;
use App\Models\Testimonial;
use App\Services\MembershipSettings;
use App\Services\TestimonialStats;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TestimonialController extends Controller
{
    public function index(Request $request)
    {
        $values = $request->validate(['status' => ['nullable', Rule::in(['pending', 'visible'])]]);
        $query = Testimonial::with('catalog')->latest();
        if ($values['status'] ?? null) {
            $query->where('is_visible', $values['status'] === 'visible');
        }

        return view('admin.testimonials', ['testimonials' => $query->paginate(15)->withQueryString(), 'settings' => app(MembershipSettings::class)->all()]);
    }

    public function update(Request $request, Testimonial $testimonial, TestimonialStats $stats)
    {
        $values = $request->validate(['is_visible' => ['required', 'boolean']]);
        DB::transaction(function () use ($request, $testimonial, $values, $stats): void {
            $catalog = Catalog::withTrashed()->whereKey($testimonial->catalog_id)->lockForUpdate()->firstOrFail();
            $testimonial->update([...$values, 'moderated_by' => $request->user()->id, 'moderated_at' => now()]);
            $stats->sync($catalog);
        });

        return redirect()->back()->with('success', $values['is_visible'] ? 'Testimoni dipublikasikan. Statistik sudah diperbarui.' : 'Testimoni disembunyikan. Statistik sudah diperbarui.');
    }
}
