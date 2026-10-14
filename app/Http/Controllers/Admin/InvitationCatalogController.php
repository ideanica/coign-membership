<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Catalog;
use App\Models\CatalogCategory;
use App\Services\CatalogMedia;
use App\Services\CatalogPricing;
use App\Services\MembershipSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InvitationCatalogController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $query = Catalog::with(['categories', 'images', 'discount'])->latest();
        if ($request->filled('q')) {
            $query->whereLike('name', '%'.$request->query('q').'%');
        }

        return view('admin.catalogs.index', ['catalogs' => $query->paginate(15)->withQueryString(), 'pricing' => app(CatalogPricing::class), 'settings' => app(MembershipSettings::class)->all()]);
    }

    public function create()
    {
        return $this->form(new Catalog);
    }

    public function edit(Catalog $catalog)
    {
        return $this->form($catalog->load(['categories', 'images', 'specifications', 'discount']));
    }

    private function form(Catalog $catalog)
    {
        return view('admin.catalogs.form', ['catalog' => $catalog, 'categories' => CatalogCategory::where(fn ($q) => $q->where('is_active', true)->orWhereIn('id', $catalog->categories->pluck('id')))->orderBy('sort_order')->get(), 'storageReady' => app(CatalogMedia::class)->ready(), 'pricing' => app(CatalogPricing::class), 'settings' => app(MembershipSettings::class)->all()]);
    }

    private function values(Request $request, ?Catalog $catalog = null): array
    {
        $first = $request->validate(['name' => ['required', 'string', 'max:160'], 'slug' => ['nullable', 'string', 'max:180']]);
        $request->merge(['slug' => filled($first['slug'] ?? null) ? $first['slug'] : ($catalog?->slug ?? (Str::slug($first['name']) ?: 'template-'.strtolower(Str::ulid())))]);
        $images = $request->input('images', []);
        $imageCount = is_array($images) ? count($images) : 0;

        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('catalogs')->ignore($catalog?->id)],
            'description' => ['required', 'string', 'max:10000'],
            'preview_url' => ['nullable', 'url:https', 'max:2048'],
            'base_price' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'estimated_days' => ['required', 'integer', 'min:1', 'max:365'],
            'category_ids' => ['required', 'array', 'min:1', 'max:20'],
            'category_ids.*' => ['required', 'integer', 'distinct', Rule::exists('catalog_categories', 'id')->where(fn ($q) => $q->where('is_active', true)->orWhereIn('id', $catalog?->categories->pluck('id')->all() ?? []))],
            'images' => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => ['required', 'array:id,url,ticket,alt'],
            'images.*.id' => ['nullable', 'integer', 'distinct', Rule::exists('catalog_images', 'id')->where('catalog_id', $catalog?->id ?? 0)],
            'images.*.url' => ['nullable', 'url:https', 'max:2048'],
            'images.*.ticket' => ['nullable', 'string', 'max:8192', 'distinct'],
            'images.*.alt' => ['nullable', 'string', 'max:200'],
            'primary_index' => ['required', 'integer', 'min:0', 'max:'.max(0, $imageCount - 1)],
            'specifications' => ['nullable', 'array', 'max:30'],
            'specifications.*' => ['array:label,value'],
            'specifications.*.label' => ['required_with:specifications.*.value', 'nullable', 'string', 'max:120'],
            'specifications.*.value' => ['required_with:specifications.*.label', 'nullable', 'string', 'max:500'],
            'is_active' => ['required', 'boolean'], 'is_featured' => ['required', 'boolean'],
        ], ['category_ids.required' => 'Pilih minimal satu kategori.', 'images.required' => 'Tambahkan minimal satu gambar.', 'images.max' => 'Maksimal 10 gambar per katalog.', 'preview_url.url' => 'Gunakan tautan preview HTTPS yang lengkap.']);
    }

    private function save(Request $request, Catalog $catalog, array $values): void
    {
        DB::transaction(function () use ($request, $catalog, $values): void {
            if ($catalog->exists) {
                $catalog = Catalog::whereKey($catalog->id)->lockForUpdate()->firstOrFail();
            }
            $catalog->fill(collect($values)->except(['category_ids', 'images', 'primary_index', 'specifications'])->all())->save();
            $catalog->categories()->sync($values['category_ids']);
            app(CatalogMedia::class)->sync($catalog, $values['images'], (int) $values['primary_index'], $request->user()->id);
            $catalog->specifications()->delete();
            foreach ($values['specifications'] ?? [] as $index => $spec) {
                if (filled($spec['label'] ?? null)) {
                    $catalog->specifications()->create([...$spec, 'sort_order' => $index]);
                }
            }
        });
    }

    public function store(Request $request)
    {
        $catalog = new Catalog;
        $this->save($request, $catalog, $this->values($request));

        return redirect()->route('admin.catalogs.edit', $catalog)->with('success', 'Katalog dibuat. Informasi, gambar, dan kategori sudah tersimpan.');
    }

    public function update(Request $request, Catalog $catalog)
    {
        $this->save($request, $catalog, $this->values($request, $catalog));

        return redirect()->route('admin.catalogs.edit', $catalog)->with('success', 'Katalog diperbarui. Gambar yang tetap dipilih dipertahankan.');
    }

    public function destroy(Catalog $catalog)
    {
        $catalog->delete();

        return redirect()->route('admin.catalogs.index')->with('success', 'Katalog diarsipkan dan tidak tampil di halaman publik.');
    }
}
