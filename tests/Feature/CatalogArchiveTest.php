<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogArchiveTest extends TestCase
{
    use RefreshDatabase;

    private function catalog(string $slug, bool $active = true): Catalog
    {
        return Catalog::create(['name' => $slug, 'slug' => $slug, 'description' => 'Test template', 'base_price' => 100000, 'estimated_days' => 3, 'is_active' => $active]);
    }

    public function test_archive_contains_only_trashed_catalogs_and_is_available_to_both_staff_roles(): void
    {
        $visible = $this->catalog('visible');
        $hidden = $this->catalog('hidden', false);
        $archived = $this->catalog('archived');
        $archived->delete();
        foreach ([false, true] as $superadmin) {
            $this->actingAs(User::factory()->create(['is_admin' => true, 'is_superadmin' => $superadmin]));
            $this->get('/admin/catalogs/archive')->assertOk()->assertViewHas('catalogs', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $archived->id);
            $this->get('/admin/catalogs')->assertOk()->assertViewHas('catalogs', fn ($rows) => $rows->total() === 2);
            $this->get('/admin/catalogs/archive?q=absent')->assertOk()->assertViewHas('catalogs', fn ($rows) => $rows->total() === 0);
        }
        $this->get('/catalog/'.$archived->slug)->assertNotFound();
        $this->assertModelExists($visible);
        $this->assertModelExists($hidden);
    }

    public function test_restore_preserves_relations_and_requires_explicit_publication(): void
    {
        $catalog = $this->catalog('restore-me');
        $image = $catalog->images()->create(['url' => 'https://example.com/image.jpg', 'is_primary' => true, 'sort_order' => 0]);
        $catalog->delete();
        $staff = User::factory()->create(['is_admin' => true]);
        $this->actingAs($staff)->patch('/admin/catalogs/'.$catalog->id.'/restore')->assertRedirect(route('admin.catalogs.edit', $catalog));
        $restored = $catalog->fresh();
        $this->assertFalse($restored->trashed());
        $this->assertFalse($restored->is_active);
        $this->assertSame($image->id, $restored->images()->sole()->id);
        $this->assertDatabaseHas('activity_logs', ['action' => 'restore', 'actor_id' => $staff->id]);
        $this->get('/catalog/'.$catalog->slug)->assertNotFound();
        $this->get('/admin/catalogs/'.$catalog->id.'/edit')->assertOk()->assertSee('Aktifkan katalog');
        $this->patch('/admin/catalogs/'.$catalog->id.'/visibility', ['is_active' => 1])->assertRedirect();
        $this->assertTrue($catalog->fresh()->is_active);
        $this->get('/catalog/'.$catalog->slug)->assertOk();
        $this->assertDatabaseHas('activity_logs', ['action' => 'publish']);
        $this->patch('/admin/catalogs/'.$catalog->id.'/visibility', ['is_active' => 0])->assertRedirect();
        $this->assertFalse($catalog->fresh()->is_active);
        $this->assertDatabaseHas('activity_logs', ['action' => 'hide']);
        $this->patch('/admin/catalogs/'.$catalog->id.'/restore')->assertNotFound();
    }

    public function test_members_and_guests_cannot_read_or_change_catalog_archive(): void
    {
        $catalog = $this->catalog('protected');
        $catalog->delete();
        $this->get('/admin/catalogs/archive')->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['password_setup_at' => now()]));
        $this->get('/admin/catalogs/archive')->assertForbidden();
        $this->patch('/admin/catalogs/'.$catalog->id.'/restore')->assertForbidden();
        $this->assertTrue($catalog->fresh()->trashed());
        $active = $this->catalog('active');
        $this->patch('/admin/catalogs/'.$active->id.'/visibility', ['is_active' => 0])->assertForbidden();
        $this->assertTrue($active->fresh()->is_active);
    }

    public function test_archived_catalog_cannot_be_published_directly_and_visibility_is_validated(): void
    {
        $catalog = $this->catalog('validation');
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->patch('/admin/catalogs/'.$catalog->id.'/visibility', ['is_active' => 'invalid'])->assertSessionHasErrors('is_active');
        $this->assertTrue($catalog->fresh()->is_active);
        $catalog->delete();
        $this->patch('/admin/catalogs/'.$catalog->id.'/visibility', ['is_active' => 1])->assertNotFound();
        $this->assertTrue($catalog->fresh()->trashed());
        $this->assertDatabaseCount('activity_logs', 0);
    }
}
