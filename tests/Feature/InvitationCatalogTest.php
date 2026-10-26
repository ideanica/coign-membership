<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\CatalogCategory;
use App\Models\CatalogDiscount;
use App\Models\User;
use App\Services\CatalogPricing;
use App\Services\InviteCatalogImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InvitationCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_catalog_pagination_keeps_ten_items_and_preserves_filters(): void
    {
        for ($index = 1; $index <= 13; $index++) {
            Catalog::create(['name' => 'Pagination template '.$index, 'slug' => 'pagination-template-'.$index, 'description' => 'Template test.', 'base_price' => 35000, 'estimated_days' => 1, 'is_active' => true]);
        }

        $this->get('/catalog?q=Pagination&sort=latest&per_page=10')
            ->assertOk()->assertViewHas('catalogs', fn ($rows) => $rows->count() === 10 && $rows->total() === 13 && $rows->lastPage() === 2 && str_contains($rows->nextPageUrl(), 'per_page=10'));
        $this->get('/catalog?q=Pagination&sort=latest&per_page=10&page=2')
            ->assertOk()->assertViewHas('catalogs', fn ($rows) => $rows->count() === 3 && $rows->currentPage() === 2);
        $this->get('/catalog?q=Pagination')->assertOk()->assertViewHas('catalogs', fn ($rows) => $rows->perPage() === 12);
        $this->getJson('/catalog?q=Pagination&per_page=10', ['X-Catalog-Fragment' => 'results'])
            ->assertOk()->assertJsonPath('total', 13)->assertJsonPath('html', fn ($html) => substr_count($html, 'class="neo-card catalog-card"') === 10 && str_contains($html, 'page=2'));
        $this->getJson('/catalog?per_page=999')->assertUnprocessable()->assertJsonValidationErrors('per_page');
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true])->save();

        return $user;
    }

    private function values(string $name = 'Aurora Wedding'): array
    {
        return ['name' => $name, 'description' => 'Desain personal untuk momen bahagia.', 'preview_url' => 'https://example.com/demo', 'base_price' => 200000, 'estimated_days' => 3, 'category_ids' => [CatalogCategory::first()->id], 'images' => [['url' => 'https://example.com/cover.jpg', 'alt' => 'Cover template'], ['url' => 'https://example.com/detail.jpg']], 'primary_index' => 0, 'specifications' => [['label' => 'Revisi', 'value' => '2 kali revisi ringan']], 'is_active' => 1, 'is_featured' => 1];
    }

    private function catalog(string $name = 'Aurora Wedding'): Catalog
    {
        $this->actingAs($this->admin())->post('/admin/catalogs', $this->values($name))->assertSessionHasNoErrors();

        return Catalog::where('name', $name)->firstOrFail();
    }

    public function test_sort_tabs_keep_filters_and_latest_is_separate_from_studio_favorites(): void
    {
        $featured = $this->catalog('Studio favorite');
        $recent = $this->catalog('Recent template');
        $recent->update(['is_featured' => false]);
        $category = $featured->categories->first();

        $this->get('/catalog?sort=featured')->assertOk()
            ->assertViewHas('catalogs', fn ($rows) => $rows->first()->id === $featured->id);
        $this->get('/catalog?sort=latest')->assertOk()
            ->assertViewHas('catalogs', fn ($rows) => $rows->first()->id === $recent->id);
        $this->get('/catalog?q=Studio&category='.$category->slug.'&sort=latest')->assertOk()
            ->assertViewHas('catalogs', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $featured->id)
            ->assertSee('id="catalog-category"', false)
            ->assertSee(e(route('catalog.index', ['q' => 'Studio', 'category' => $category->slug, 'sort' => 'price_low']).'#templates'), false);
    }

    public function test_async_catalog_returns_only_results_and_applies_filters(): void
    {
        $catalog = $this->catalog('Aurora async');
        $this->catalog('Other template');

        $response = $this->getJson('/catalog?q=Aurora&sort=price_low', ['X-Catalog-Fragment' => 'results']);
        $response->assertOk()->assertJsonPath('total', 1);
        $this->assertStringContainsString($catalog->name, $response->json('html'));
        $this->assertStringNotContainsString('Other template', $response->json('html'));
        $this->assertStringNotContainsString('catalog-hero', $response->json('html'));
        $this->assertStringNotContainsString('<html', $response->json('html'));
        $this->getJson('/catalog?sort=invalid', ['X-Catalog-Fragment' => 'results'])
            ->assertUnprocessable()->assertJsonValidationErrors('sort');
    }

    public function test_catalog_and_admin_empty_pages_render_and_only_admins_manage_templates(): void
    {
        $this->get('/catalog')->assertOk()->assertSee('Katalog sedang disiapkan');
        $this->actingAs(User::factory()->create())->post('/admin/catalogs', $this->values())->assertForbidden();
        $this->get('/admin/discounts')->assertForbidden();
        $this->actingAs($this->admin());
        foreach (['/admin/catalogs', '/admin/catalogs/create', '/admin/discounts', '/admin/discounts/create', '/admin/catalog-categories'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_create_and_edit_keep_image_references_and_publish_multi_category_template(): void
    {
        $admin = $this->admin();
        $values = $this->values();
        $values['category_ids'] = CatalogCategory::take(2)->pluck('id')->all();
        $this->actingAs($admin)->post('/admin/catalogs', $values)
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute('admin.catalogs.index')
            ->assertSessionHas('success', 'Katalog dibuat. Informasi, gambar, dan kategori sudah tersimpan.');
        $catalog = Catalog::firstOrFail();
        $ids = $catalog->images->pluck('id')->all();
        $this->assertCount(2, $catalog->categories);
        $this->get('/catalog/'.$catalog->slug)->assertOk()->assertSee('Revisi')->assertSee('https://example.com/demo');
        $this->get('/admin/catalogs/'.$catalog->id.'/edit')->assertOk();
        $values['images'] = array_map(fn ($id) => ['id' => $id, 'alt' => 'Foto dipertahankan'], $ids);
        $values['name'] = 'Nama template diperbarui';
        $this->put('/admin/catalogs/'.$catalog->id, $values)->assertSessionHasNoErrors();
        $this->assertSame($ids, $catalog->fresh()->images->pluck('id')->all());
        $this->assertDatabaseHas('catalog_images', ['id' => $ids[0], 'external_url' => 'https://example.com/cover.jpg']);
        $this->delete('/admin/catalogs/'.$catalog->id)->assertRedirect('/admin/catalogs');
        $this->get('/catalog/'.$catalog->slug)->assertNotFound();
        $this->assertDatabaseCount('catalog_images', 2);
    }

    public function test_wrong_gallery_owner_and_unsafe_links_rollback_catalog_updates(): void
    {
        $first = $this->catalog();
        $second = $this->catalog('Other template');
        $values = $this->values('Should not save');
        $values['images'] = [['id' => $second->images->first()->id]];
        $this->put('/admin/catalogs/'.$first->id, $values)->assertSessionHasErrors('images.0.id');
        $this->assertSame('Aurora Wedding', $first->fresh()->name);
        $values['images'] = [['url' => 'javascript:alert(1)']];
        $this->put('/admin/catalogs/'.$first->id, $values)->assertSessionHasErrors('images.0.url');
        $values['images'] = 'malformed';
        $this->put('/admin/catalogs/'.$first->id, $values)->assertSessionHasErrors('images');
        $this->assertDatabaseCount('catalog_images', 4);
    }

    public function test_upload_goes_to_external_disk_and_ticket_is_owned_single_use_and_expiring(): void
    {
        Storage::fake('catalog_cloud');
        $admin = $this->admin();
        $response = $this->actingAs($admin)->post('/admin/catalogs/upload', ['image' => UploadedFile::fake()->image('cover.jpg')])->assertOk();
        $ticket = $response->json('ticket');
        $upload = DB::table('catalog_uploads')->first();
        Storage::disk('catalog_cloud')->assertExists($upload->path);
        $values = $this->values();
        $values['images'] = [['ticket' => $ticket]];
        $this->actingAs($this->admin())->post('/admin/catalogs', $values)->assertSessionHasErrors('images');
        $this->assertDatabaseCount('catalogs', 0);
        $this->actingAs($admin)->post('/admin/catalogs', $values)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('catalog_images', ['disk' => 'catalog_cloud', 'path' => $upload->path]);
        $this->post('/admin/catalogs', [...$values, 'name' => 'Replay'])->assertSessionHasErrors('images');
        $this->assertDatabaseCount('catalogs', 1);
        $response = $this->post('/admin/catalogs/upload', ['image' => UploadedFile::fake()->image('other.png')])->assertOk();
        $this->travel(2)->hours();
        $values['images'] = [['ticket' => $response->json('ticket')]];
        $this->post('/admin/catalogs', [...$values, 'name' => 'Expired'])->assertSessionHasErrors('images');
    }

    public function test_upload_rejects_unsupported_files_and_non_admins(): void
    {
        $this->actingAs(User::factory()->create())->post('/admin/catalogs/upload', ['image' => UploadedFile::fake()->image('cover.jpg')])->assertForbidden();
        $this->actingAs($this->admin())->post('/admin/catalogs/upload', ['image' => UploadedFile::fake()->create('bad.svg', 10, 'image/svg+xml')])->assertSessionHasErrors('image');
        $this->post('/admin/catalogs/upload', ['image' => UploadedFile::fake()->image('huge.jpg')->size(5000)])->assertSessionHasErrors('image');
        $this->assertDatabaseCount('catalog_uploads', 0);
    }

    public function test_discounts_percent_fixed_schedule_and_price_sort_use_actual_final_prices(): void
    {
        $catalog = $this->catalog();
        $this->post('/admin/discounts', ['catalog_id' => $catalog->id, 'name' => 'Promo', 'type' => 'percent', 'value' => 25, 'is_active' => 1])->assertSessionHasNoErrors();
        $discount = CatalogDiscount::firstOrFail();
        $this->assertSame(150000, app(CatalogPricing::class)->finalPrice($catalog->fresh()));
        $this->get('/admin/discounts')->assertOk();
        $this->get('/admin/discounts/'.$discount->id.'/edit')->assertOk();
        $this->post('/admin/discounts', ['catalog_id' => $catalog->id, 'name' => 'Duplicate', 'type' => 'percent', 'value' => 10, 'is_active' => 1])->assertSessionHasErrors('catalog_id');
        $second = $this->catalog('Second price');
        $second->update(['base_price' => 175000]);
        $this->get('/catalog?sort=price_low')->assertOk()->assertViewHas('catalogs', fn ($rows) => $rows->first()->id === $catalog->id);
        $this->get('/catalog?sort=price_high')->assertOk()->assertViewHas('catalogs', fn ($rows) => $rows->first()->id === $second->id);
        $values = ['catalog_id' => $catalog->id, 'name' => 'Fixed', 'type' => 'fixed', 'value' => 500000, 'is_active' => 1, 'starts_at' => null, 'ends_at' => now()->addDay()->toDateTimeString()];
        $this->put('/admin/discounts/'.$discount->id, $values)->assertSessionHasNoErrors();
        $this->assertSame(0, app(CatalogPricing::class)->finalPrice($catalog->fresh()));
        $this->travel(2)->days();
        $this->assertSame(200000, app(CatalogPricing::class)->finalPrice($catalog->fresh()));
        $this->delete('/admin/discounts/'.$discount->id)->assertRedirect('/admin/discounts');
    }

    public function test_invalid_percent_or_schedule_cannot_be_saved(): void
    {
        $catalog = $this->catalog();
        $values = ['catalog_id' => $catalog->id, 'name' => 'Bad', 'type' => 'percent', 'value' => 101, 'is_active' => 1];
        $this->post('/admin/discounts', $values)->assertSessionHasErrors('value');
        $this->post('/admin/discounts', [...$values, 'value' => 10, 'starts_at' => '2026-12-20', 'ends_at' => '2026-12-01'])->assertSessionHasErrors('ends_at');
        $this->assertDatabaseCount('catalog_discounts', 0);
    }

    public function test_category_filter_and_inactive_templates_are_respected(): void
    {
        $catalog = $this->catalog();
        $category = $catalog->categories->first();
        $this->get('/catalog?category='.$category->slug)->assertSee($catalog->name);
        $this->get('/catalog?category=no-match')->assertDontSee($catalog->name)->assertSee('Belum ada template yang cocok');
        $catalog->update(['is_active' => false]);
        $this->get('/catalog')->assertDontSee($catalog->name);
        $this->get('/catalog/'.$catalog->slug)->assertNotFound();
    }

    public function test_import_is_atomic_repeated_import_skips_and_images_stay_external(): void
    {
        $payload = ['catalogs' => [['name' => 'Imported design', 'slug' => 'imported-design', 'description' => '<p>Real catalog description</p>', 'preview_url' => 'https://example.com/real', 'base_price' => 150000, 'estimated_days' => 3, 'categories' => [['name' => 'Wedding', 'slug' => 'wedding']], 'images' => [['external_url' => 'https://example.com/original.jpg']], 'specifications' => [['label' => 'Feature', 'value' => 'Music']]]]];
        $importer = app(InviteCatalogImport::class);
        $payload['catalogs'][0]['rating_average'] = 4.75;
        $payload['catalogs'][0]['reviews_count'] = 3;
        $payload['catalogs'][0]['created_at'] = '2026-08-19 15:00:00';
        $payload['catalogs'][0]['updated_at'] = '2026-08-20 15:00:00';
        $payload['categories'] = [['name' => 'Games', 'slug' => 'games', 'is_active' => false, 'sort_order' => 9]];
        $this->assertSame(['imported' => 1, 'skipped' => 0], $importer->run($payload));
        $this->assertSame(['imported' => 0, 'skipped' => 1], $importer->run($payload));
        $this->assertDatabaseHas('catalog_images', ['external_url' => 'https://example.com/original.jpg']);
        $this->assertDatabaseHas('catalogs', ['description' => "Real catalog description\n"]);
        $this->assertDatabaseHas('catalogs', ['rating_average' => 4.75, 'reviews_count' => 3, 'created_at' => '2026-08-19 15:00:00', 'updated_at' => '2026-08-20 15:00:00']);
        $this->assertDatabaseHas('catalog_categories', ['slug' => 'games', 'is_active' => false, 'sort_order' => 9]);
        $this->get('/catalog/imported-design')->assertOk()->assertSee('4,8 / 5')->assertSee('3 ulasan');
        $invalid = $payload;
        $invalid['catalogs'][0]['slug'] = 'second-import';
        $invalid['catalogs'][0]['images'] = [['path' => 'catalogs/../private/file', 'disk' => 'catalog_cloud']];
        try {
            $importer->run($invalid);
            $this->fail('Unsafe path must fail');
        } catch (ValidationException) {
            $this->assertDatabaseCount('catalogs', 1);
        }
    }
}
