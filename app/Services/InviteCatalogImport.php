<?php

namespace App\Services;

use App\Models\Catalog;
use App\Models\CatalogCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InviteCatalogImport
{
    public function run(array $payload, bool $replace = false): array
    {
        $rows = $payload['catalogs'] ?? $payload;
        Validator::make(['catalogs' => $rows], ['catalogs' => ['required', 'array', 'max:10000']])->validate();
        $categories = $payload['categories'] ?? [];
        Validator::make(['categories' => $categories], [
            'categories' => ['array', 'max:1000'],
            'categories.*.name' => ['required', 'string', 'max:100'],
            'categories.*.slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'categories.*.is_active' => ['sometimes', 'boolean'],
            'categories.*.sort_order' => ['sometimes', 'integer', 'min:0', 'max:10000'],
        ])->validate();

        return DB::transaction(function () use ($rows, $replace, $categories): array {
            foreach ($categories as $category) {
                CatalogCategory::updateOrCreate(['slug' => $category['slug']], [
                    'name' => $category['name'], 'is_active' => $category['is_active'] ?? true,
                    'sort_order' => $category['sort_order'] ?? 0,
                ]);
            }
            $counts = ['imported' => 0, 'skipped' => 0];
            foreach ($rows as $row) {
                $values = Validator::make($row, [
                    'name' => ['required', 'string', 'max:160'], 'slug' => ['required', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
                    'description' => ['required', 'string', 'max:10000'], 'preview_url' => ['nullable', 'url:https', 'max:2048'],
                    'base_price' => ['required', 'integer', 'min:0', 'max:1000000000'], 'estimated_days' => ['required', 'integer', 'min:1', 'max:365'],
                    'is_active' => ['sometimes', 'boolean'], 'is_featured' => ['sometimes', 'boolean'],
                    'rating_average' => ['sometimes', 'numeric', 'min:0', 'max:5'], 'reviews_count' => ['sometimes', 'integer', 'min:0'],
                    'created_at' => ['sometimes', 'date'], 'updated_at' => ['sometimes', 'date'],
                    'categories' => ['required', 'array', 'min:1', 'max:20'], 'categories.*.name' => ['required', 'string', 'max:100'], 'categories.*.slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
                    'images' => ['required', 'array', 'min:1', 'max:10'], 'images.*.external_url' => ['nullable', 'url:https', 'max:2048'], 'images.*.path' => ['nullable', 'string', 'max:255', 'regex:#^catalogs/[a-zA-Z0-9_./-]+$#'], 'images.*.disk' => ['nullable', 'in:catalog_cloud'], 'images.*.alt_text' => ['nullable', 'string', 'max:200'],
                    'specifications' => ['sometimes', 'array', 'max:30'], 'specifications.*.label' => ['required', 'string', 'max:120'], 'specifications.*.value' => ['required', 'string', 'max:500'],
                    'discount' => ['nullable', 'array'], 'discount.name' => ['required_with:discount', 'string', 'max:160'], 'discount.type' => ['required_with:discount', 'in:percent,fixed'], 'discount.value' => ['required_with:discount', 'integer', 'min:1', 'max:1000000000'], 'discount.is_active' => ['sometimes', 'boolean'], 'discount.starts_at' => ['nullable', 'date'], 'discount.ends_at' => ['nullable', 'date'],
                ])->validate();
                $catalog = Catalog::withTrashed()->where('slug', $values['slug'])->first();
                if ($catalog && ! $replace) {
                    $counts['skipped']++;

                    continue;
                }
                $catalog ??= new Catalog;
                $catalog->fill(collect($values)->except(['categories', 'images', 'specifications', 'discount'])->all());
                $catalog->description = html_entity_decode(strip_tags(str_replace(['</p>', '</li>', '<br>', '<br/>', '<br />'], "\n", $catalog->description)), ENT_QUOTES | ENT_HTML5);
                $catalog->forceFill(collect($values)->only(['created_at', 'updated_at'])->all());
                $catalog->save();
                $ids = [];
                foreach ($values['categories'] as $category) {
                    $ids[] = CatalogCategory::firstOrCreate(['slug' => $category['slug']], ['name' => $category['name'], 'is_active' => true])->id;
                }
                $catalog->categories()->sync($ids);
                $catalog->images()->delete();
                foreach ($values['images'] as $index => $image) {
                    if (filled($image['external_url'] ?? null)) {
                        $data = ['external_url' => $image['external_url']];
                    } else {
                        if (($image['disk'] ?? null) !== 'catalog_cloud' || blank($image['path'] ?? null) || Str::contains($image['path'], '..')) {
                            throw ValidationException::withMessages(['images' => 'Gambar impor harus berupa URL HTTPS atau file Cloudflare R2 yang valid.']);
                        }
                        $data = ['disk' => 'catalog_cloud', 'path' => $image['path']];
                    }
                    $catalog->images()->create([...$data, 'alt_text' => $image['alt_text'] ?? $catalog->name, 'sort_order' => $index, 'is_primary' => $index === 0]);
                }
                $catalog->specifications()->delete();
                foreach ($values['specifications'] ?? [] as $index => $spec) {
                    $catalog->specifications()->create([...$spec, 'sort_order' => $index]);
                }
                if (filled($values['discount'] ?? null)) {
                    $discount = $values['discount'];
                    if ($discount['type'] === 'percent' && $discount['value'] > 100) {
                        throw ValidationException::withMessages(['discount' => 'Diskon impor tidak boleh melebihi 100%.']);
                    }
                    $catalog->discount()->updateOrCreate([], $discount);
                } elseif ($replace) {
                    $catalog->discount()->delete();
                }
                $counts['imported']++;
            }

            return $counts;
        });
    }
}
