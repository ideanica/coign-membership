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
    public function index()
    {
        return view('admin.catalogs.categories', ['categories' => CatalogCategory::withCount('catalogs')->orderBy('sort_order')->get(), 'settings' => app(MembershipSettings::class)->all()]);
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
        CatalogCategory::create($this->values($request));

        return back()->with('success', 'Kategori ditambahkan.');
    }

    public function update(Request $request, CatalogCategory $category)
    {
        $category->update($this->values($request, $category));

        return back()->with('success', 'Kategori diperbarui.');
    }

    public function destroy(CatalogCategory $category)
    {
        $category->update(['is_active' => false]);

        return back()->with('success', 'Kategori dinonaktifkan. Katalog terkait tetap tersimpan.');
    }
}
