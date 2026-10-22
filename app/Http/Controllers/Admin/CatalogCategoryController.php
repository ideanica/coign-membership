<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogCategory;
use App\Services\MembershipSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CatalogCategoryController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
        $query = CatalogCategory::withCount('catalogs')->orderBy('sort_order')->orderBy('name')->orderBy('id');
        if (filled($filters['q'] ?? null)) {
            $query->where(fn ($builder) => $builder->whereLike('name', '%'.$filters['q'].'%')->orWhereLike('slug', '%'.$filters['q'].'%'));
        }
        if (filled($filters['status'] ?? null)) {
            $query->where('is_active', $filters['status'] === 'active');
        }
        $lastOrder = CatalogCategory::max('sort_order');

        return view('admin.catalogs.categories', [
            'categories' => $query->paginate(15)->withQueryString(),
            'nextSortOrder' => min(10000, ($lastOrder ?? -1) + 1),
            'editingCategory' => $request->old('_workspace_form') === 'category-edit' ? CatalogCategory::find($request->old('_category_id')) : null,
            'settings' => app(MembershipSettings::class)->all(),
        ]);
    }

    private function values(Request $request, ?CatalogCategory $category = null): array
    {
        $values = $request->validate(['name' => ['required', 'string', 'max:100'], 'sort_order' => ['required', 'integer', 'min:0', 'max:10000'], 'is_active' => ['required', 'boolean']]);
        $slug = $category?->slug ?? (Str::slug($values['name']) ?: 'kategori-'.strtolower(Str::ulid()));
        validator(['slug' => $slug], ['slug' => [Rule::unique('catalog_categories')->ignore($category?->id)]])->validate();

        return [...$values, 'slug' => $slug];
    }

    public function store(Request $request)
    {
        $request->merge(['_workspace_form' => 'category-create']);
        CatalogCategory::create($this->values($request));

        return back()->with('success', 'Kategori ditambahkan.');
    }

    public function update(Request $request, CatalogCategory $category)
    {
        $request->merge(['_workspace_form' => 'category-edit', '_category_id' => $category->id]);
        $category->update($this->values($request, $category));

        return back()->with('success', 'Kategori diperbarui.');
    }

    public function destroy(CatalogCategory $category)
    {
        $category->update(['is_active' => false]);

        return back()->with('success', 'Kategori dinonaktifkan. Katalog terkait tetap tersimpan.');
    }
}
