<?php

namespace App\Console\Commands;

use App\Services\InviteCatalogImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ImportInviteCatalog extends Command
{
    protected $signature = 'catalog:import-invite {--file= : JSON export path} {--source=invite_legacy : Read-only source database connection} {--replace : Replace matching catalog slugs}';

    protected $description = 'Import invite-me catalogs, categories, external image references and discounts without importing customer accounts.';

    public function handle(InviteCatalogImport $importer): int
    {
        try {
            if ($path = $this->option('file')) {
                if (! is_file($path) || filesize($path) > 20 * 1024 * 1024) {
                    $this->error('Gunakan file JSON yang tersedia, maksimal 20 MB.');

                    return self::FAILURE;
                }
                $payload = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            } else {
                $source = $this->option('source');
                if (! config('database.connections.'.$source.'.url') || $source === config('database.default')) {
                    $this->error('Atur INVITE_DATABASE_URL untuk database lama atau gunakan --file=export.json.');

                    return self::FAILURE;
                }
                $db = DB::connection($source);
                $categories = $db->table('categories')->get()->keyBy('id');
                $discounts = $db->table('discounts')->get()->keyBy('id');
                $payload = [];
                foreach ($db->table('catalogs')->whereNull('deleted_at')->orderBy('id')->get() as $catalog) {
                    $ids = Schema::connection($source)->hasTable('catalog_category') ? $db->table('catalog_category')->where('catalog_id', $catalog->id)->pluck('category_id')->all() : [$catalog->category_id];
                    $images = $db->table('catalog_images')->where('catalog_id', $catalog->id)->orderByDesc('is_primary')->orderBy('sort_order')->get()->map(fn ($image) => str_starts_with($image->path, 'https://') ? ['external_url' => $image->path, 'alt_text' => $image->alt_text] : ['path' => $image->path, 'disk' => $image->disk ?? 'public', 'alt_text' => $image->alt_text]);
                    $discount = $discounts->get($catalog->discount_id);
                    $payload[] = [
                        'name' => $catalog->name, 'slug' => $catalog->slug, 'description' => $catalog->description, 'preview_url' => $catalog->preview_url, 'base_price' => $catalog->base_price, 'estimated_days' => $catalog->estimated_days, 'is_active' => (bool) $catalog->is_active, 'is_featured' => (bool) $catalog->is_featured,
                        'rating_average' => $catalog->rating_average ?? 0, 'reviews_count' => $catalog->reviews_count ?? 0,
                        'created_at' => $catalog->created_at, 'updated_at' => $catalog->updated_at,
                        'categories' => collect($ids)->map(fn ($id) => ['name' => $categories[$id]->name, 'slug' => $categories[$id]->slug])->all(), 'images' => $images->all(),
                        'specifications' => $db->table('catalog_specifications')->where('catalog_id', $catalog->id)->orderBy('sort_order')->get(['label', 'value'])->map(fn ($row) => (array) $row)->all(),
                        'discount' => $discount ? ['name' => $discount->name, 'type' => $discount->type, 'value' => $discount->value, 'starts_at' => $discount->starts_at, 'ends_at' => $discount->ends_at, 'is_active' => (bool) $discount->is_active] : null,
                    ];
                }
                $payload = ['catalogs' => $payload, 'categories' => $categories->map(fn ($category) => [
                    'name' => $category->name, 'slug' => $category->slug,
                    'is_active' => (bool) $category->is_active, 'sort_order' => $category->sort_order,
                ])->values()->all()];
            }
            $counts = $importer->run($payload, (bool) $this->option('replace'));
            $this->info('Diimpor: '.$counts['imported'].'; dilewati karena slug sudah ada: '.$counts['skipped'].'.');

            return self::SUCCESS;
        } catch (ValidationException $error) {
            $this->error(collect($error->errors())->flatten()->join(' '));
        } catch (\Throwable $error) {
            report($error);
            $this->error('Impor gagal. Tidak ada perubahan katalog yang disimpan. Periksa format file atau koneksi database sumber.');
        }

        return self::FAILURE;
    }
}
