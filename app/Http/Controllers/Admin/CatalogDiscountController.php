<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Catalog;
use App\Models\CatalogDiscount;
use App\Services\CatalogPricing;
use App\Services\MembershipSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CatalogDiscountController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $query = CatalogDiscount::whereHas('catalog')->with('catalog.discount')->latest()->orderByDesc('id');

        if (filled($filters['q'] ?? null)) {
            $search = '%'.$filters['q'].'%';
            $query->where(fn ($builder) => $builder->whereLike('name', $search)
                ->orWhereHas('catalog', fn ($catalog) => $catalog->whereLike('name', $search)));
        }

        return view('admin.discounts.index', [
            'discounts' => $query->paginate(15)->withQueryString(),
            'settings' => app(MembershipSettings::class)->all(),
            'pricing' => app(CatalogPricing::class),
        ]);
    }

    public function create()
    {
        return $this->form(new CatalogDiscount);
    }

    public function edit(CatalogDiscount $discount)
    {
        abort_unless($discount->catalog, 404);

        return $this->form($discount->load('catalog'));
    }

    private function form(CatalogDiscount $discount)
    {
        $catalogs = $discount->exists
            ? Catalog::whereKey($discount->catalog_id)->get()
            : Catalog::whereDoesntHave('discount')->orderBy('name')->get();

        return view('admin.discounts.form', ['discount' => $discount, 'catalogs' => $catalogs, 'settings' => app(MembershipSettings::class)->all()]);
    }

    private function values(Request $request, ?CatalogDiscount $discount = null): array
    {
        $values = $request->validate(['catalog_id' => ['required', 'integer', Rule::exists('catalogs', 'id')->whereNull('deleted_at'), Rule::unique('catalog_discounts', 'catalog_id')->ignore($discount?->id)], 'name' => ['required', 'string', 'max:160'], 'type' => ['required', Rule::in(['percent', 'fixed'])], 'value' => ['required', 'integer', 'min:1', 'max:1000000000'], 'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', Rule::when($request->filled('starts_at'), 'after_or_equal:starts_at')], 'is_active' => ['required', 'boolean']]);
        if ($values['type'] === 'percent' && $values['value'] > 100) {
            throw ValidationException::withMessages(['value' => 'Diskon persentase tidak boleh melebihi 100%.']);
        }
        if ($discount && (int) $values['catalog_id'] !== $discount->catalog_id) {
            throw ValidationException::withMessages(['catalog_id' => 'Katalog penerima diskon tidak dapat diganti.']);
        }

        return $values;
    }

    public function store(Request $request)
    {
        $values = $this->values($request);
        DB::transaction(function () use ($values): void {
            $catalog = Catalog::whereKey($values['catalog_id'])->lockForUpdate()->firstOrFail();
            if ($catalog->discount()->exists()) {
                throw ValidationException::withMessages(['catalog_id' => 'Katalog sudah memiliki diskon. Edit diskon yang tersedia.']);
            }
            CatalogDiscount::create($values);
        });

        return redirect()->route('admin.discounts.index')->with('success', 'Diskon dibuat. Harga katalog mengikuti jadwal dan status diskon.');
    }

    public function update(Request $request, CatalogDiscount $discount)
    {
        $discount->update($this->values($request, $discount));

        return redirect()->route('admin.discounts.index')->with('success', 'Diskon diperbarui.');
    }

    public function destroy(CatalogDiscount $discount)
    {
        $discount->delete();

        return redirect()->route('admin.discounts.index')->with('success', 'Diskon dihapus. Katalog kembali menggunakan harga normal.');
    }
}
