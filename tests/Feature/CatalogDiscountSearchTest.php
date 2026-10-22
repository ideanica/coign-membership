<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\CatalogDiscount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class CatalogDiscountSearchTest extends TestCase
{
    use RefreshDatabase;

    private function discount(string $promo, string $template): CatalogDiscount
    {
        $catalog = Catalog::create(['name' => $template, 'slug' => str($template)->slug()->toString(), 'description' => 'Template test.', 'base_price' => 100000, 'estimated_days' => 3, 'is_active' => true]);

        return CatalogDiscount::create(['catalog_id' => $catalog->id, 'name' => $promo, 'type' => 'percent', 'value' => 10, 'is_active' => true]);
    }

    #[TestWith(['launch'])]
    #[TestWith(['celebration'])]
    public function test_search_matches_promo_or_template_and_excludes_archived_catalogs(string $search): void
    {
        $match = $this->discount('Launch offer', 'Celebration invitation');
        $other = $this->discount('Unrelated offer', 'Unrelated invitation');
        $archived = $this->discount('Launch archived', 'Celebration archived');
        $archived->catalog->delete();
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get('/admin/discounts?q='.$search)->assertOk()
            ->assertSee($match->name)->assertDontSee($other->name)->assertDontSee($archived->name)
            ->assertViewHas('discounts', fn ($rows) => $rows->total() === 1)
            ->assertSee('value="'.$search.'"', false);
    }

    public function test_search_keeps_query_on_next_page(): void
    {
        for ($index = 1; $index <= 16; $index++) {
            $this->discount('Launch offer '.$index, 'Invitation '.$index);
        }
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get('/admin/discounts?q=launch')->assertOk()
            ->assertViewHas('discounts', fn ($rows) => $rows->total() === 16 && $rows->count() === 15 && str_contains($rows->nextPageUrl(), 'q=launch'));
        $this->get('/admin/discounts?q=launch&page=2')->assertOk()
            ->assertViewHas('discounts', fn ($rows) => $rows->count() === 1);
    }

    public function test_empty_search_shows_all_discounts_and_unmatched_search_explains_empty_result(): void
    {
        $discount = $this->discount('Seasonal offer', 'Seasonal invitation');
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get('/admin/discounts?q=')->assertOk()->assertSee($discount->name);
        $this->get('/admin/discounts?q=unmatched')->assertOk()
            ->assertDontSee($discount->name)->assertSee('Tidak ada diskon yang sesuai.');
    }

    #[TestWith(['q[bad]=value'])]
    #[TestWith(['q=aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'])]
    public function test_invalid_search_is_rejected(string $query): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->getJson('/admin/discounts?'.$query)->assertUnprocessable()->assertJsonValidationErrors('q');
    }
}
