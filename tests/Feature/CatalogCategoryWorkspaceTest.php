<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\CatalogCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogCategoryWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private function category(string $name, bool $active = true, int $order = 0): CatalogCategory
    {
        return CatalogCategory::create(['name' => $name, 'slug' => str($name)->slug()->toString(), 'sort_order' => $order, 'is_active' => $active]);
    }

    public function test_workspace_uses_searchable_list_and_separate_create_and_edit_dialogs(): void
    {
        $category = $this->category('Workspace celebration');
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $response = $this->get('/admin/catalog-categories')->assertOk();

        $response->assertSee('Workspace celebration')->assertSee('Daftar kategori')
            ->assertSee('data-dialog-open="category-create-dialog"', false)
            ->assertSee('data-dialog-open="category-edit-dialog"', false)
            ->assertSee('data-confirm=', false)->assertSee('name="q"', false)
            ->assertSee('id="category-create-dialog"', false)->assertSee('id="category-edit-dialog"', false);
        $this->assertSame(1, substr_count($response->getContent(), 'name="_workspace_form" value="category-create"'));
        $this->assertSame(1, substr_count($response->getContent(), 'name="_workspace_form" value="category-edit"'));
    }

    public function test_search_and_status_filters_combine_without_showing_other_categories(): void
    {
        $match = $this->category('Workspace matching inactive', false);
        $active = $this->category('Workspace matching active');
        $other = $this->category('Workspace unrelated', false);
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get('/admin/catalog-categories?q=matching&status=inactive')->assertOk()
            ->assertSee($match->name)->assertDontSee($active->name)->assertDontSee($other->name)
            ->assertViewHas('categories', fn ($rows) => $rows->total() === 1);
        $this->get('/admin/catalog-categories?q='.$active->slug.'&status=active')->assertOk()->assertSee($active->name)->assertDontSee($match->name);
    }

    public function test_list_is_paginated_and_keeps_search_on_next_page(): void
    {
        for ($i = 0; $i < 17; $i++) {
            $this->category('Workspace paginated '.$i, true, $i);
        }
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get('/admin/catalog-categories?q=paginated')->assertOk()
            ->assertViewHas('categories', fn ($rows) => $rows->total() === 17 && $rows->count() === 15 && str_contains($rows->nextPageUrl(), 'q=paginated'));
        $this->get('/admin/catalog-categories?q=paginated&page=2')->assertOk()
            ->assertViewHas('categories', fn ($rows) => $rows->count() === 2);
    }

    public function test_create_edit_and_deactivate_preserve_linked_catalogs_and_slug(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->from('/admin/catalog-categories')->post('/admin/catalog-categories', ['name' => 'Workspace launch', 'sort_order' => 40, 'is_active' => 1])->assertSessionHasNoErrors()->assertRedirect('/admin/catalog-categories');
        $category = CatalogCategory::where('slug', 'workspace-launch')->sole();
        $catalog = Catalog::create(['name' => 'Linked template', 'slug' => 'linked-workspace', 'description' => 'Description', 'base_price' => 100000, 'estimated_days' => 3, 'is_active' => true]);
        $category->catalogs()->attach($catalog);

        $this->put('/admin/catalog-categories/'.$category->id, ['name' => 'Workspace renamed', 'sort_order' => 2, 'is_active' => 1])->assertSessionHasNoErrors();
        $this->delete('/admin/catalog-categories/'.$category->id)->assertSessionHasNoErrors();

        $this->assertDatabaseHas('catalog_categories', ['id' => $category->id, 'name' => 'Workspace renamed', 'slug' => 'workspace-launch', 'sort_order' => 2, 'is_active' => false]);
        $this->assertTrue($category->fresh()->catalogs()->whereKey($catalog->id)->exists());
        $this->assertDatabaseHas('catalogs', ['id' => $catalog->id, 'is_active' => true]);
        $this->put('/admin/catalog-categories/'.$category->id, ['name' => 'Workspace renamed', 'sort_order' => 2, 'is_active' => 1])->assertSessionHasNoErrors();
        $this->assertTrue($category->fresh()->is_active);
    }

    public function test_invalid_create_reopens_create_dialog_and_handles_malformed_old_input(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $response = $this->followingRedirects()->from('/admin/catalog-categories')->post('/admin/catalog-categories', ['name' => ['bad'], 'sort_order' => -1, 'is_active' => 1, '_workspace_form' => 'category-edit'])->assertOk();

        $this->assertMatchesRegularExpression('/<dialog[^>]+id="category-create-dialog"[^>]+data-open-on-error/', $response->getContent());
    }

    public function test_invalid_edit_reopens_correct_dialog_with_values_and_bound_category(): void
    {
        $category = $this->category('Workspace retry');
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $response = $this->followingRedirects()->from('/admin/catalog-categories')->put('/admin/catalog-categories/'.$category->id, ['name' => 'Workspace unsaved', 'sort_order' => -2, 'is_active' => 0, '_category_id' => 999])->assertOk();

        $response->assertSee('value="Workspace unsaved"', false)->assertSee('action="'.route('admin.catalog-categories.update', $category).'"', false);
        $this->assertMatchesRegularExpression('/<dialog[^>]+id="category-edit-dialog"[^>]+data-open-on-error/', $response->getContent());
        $this->assertDatabaseHas('catalog_categories', ['id' => $category->id, 'name' => 'Workspace retry', 'sort_order' => 0]);
    }

    public function test_search_input_is_validated_and_empty_state_is_clear(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->getJson('/admin/catalog-categories?q[bad]=value&status=invalid')->assertUnprocessable()->assertJsonValidationErrors(['q', 'status']);
        $this->get('/admin/catalog-categories?q=workspace-no-matches')->assertOk()->assertSee('Tidak ada kategori yang sesuai.');
    }

    public function test_guests_and_members_cannot_access_category_management(): void
    {
        $this->get('/admin/catalog-categories')->assertRedirect('/login');
        $this->actingAs(User::factory()->create());
        $this->get('/admin/catalog-categories')->assertForbidden();
        $this->post('/admin/catalog-categories', ['name' => 'Forbidden', 'sort_order' => 1, 'is_active' => 1])->assertForbidden();
    }
}
