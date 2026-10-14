<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\Testimonial;
use App\Models\User;
use App\Services\TestimonialStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestimonialsTest extends TestCase
{
    use RefreshDatabase;

    private function catalog(): Catalog
    {
        return Catalog::create(['name' => 'Test template', 'slug' => 'test-template', 'description' => 'A personal template.', 'base_price' => 100000, 'estimated_days' => 1, 'is_active' => true]);
    }

    public function test_guest_review_requires_moderation_and_visible_reviews_drive_statistics(): void
    {
        $catalog = $this->catalog();
        $this->post('/catalog/test-template/testimonials', ['reviewer_name' => 'A customer', 'rating' => 5, 'comment' => 'Desainnya sangat bagus.'])->assertSessionHasNoErrors();
        $review = Testimonial::firstOrFail();
        $this->assertFalse($review->is_visible);
        $this->get('/')->assertDontSee('Desainnya sangat bagus.');
        $this->get('/catalog/test-template')->assertOk()->assertViewHas('reviewStats', fn ($stats) => $stats['total'] === 0);
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->put('/admin/testimonials/'.$review->id, ['is_visible' => 1])->assertSessionHasNoErrors();
        $this->assertSame(1, $catalog->fresh()->reviews_count);
        $this->assertSame('5.00', $catalog->fresh()->rating_average);
        $this->get('/')->assertSee('Desainnya sangat bagus.');
        $this->get('/catalog/test-template')->assertSee('Desainnya sangat bagus.')->assertViewHas('reviewStats', fn ($stats) => $stats['distribution'][5]['count'] === 1);
        $this->put('/admin/testimonials/'.$review->id, ['is_visible' => 0])->assertSessionHasNoErrors();
        $this->assertSame(0, $catalog->fresh()->reviews_count);
    }

    public function test_member_updates_one_review_and_returns_to_pending_without_forging_publication(): void
    {
        $catalog = $this->catalog();
        $user = User::factory()->create(['name' => 'Member author']);
        $review = Testimonial::create(['catalog_id' => $catalog->id, 'user_id' => $user->id, 'reviewer_name' => $user->name, 'rating' => 5, 'comment' => 'Original experience.', 'is_visible' => true]);
        app(TestimonialStats::class)->sync($catalog);
        $this->actingAs($user)->post('/catalog/test-template/testimonials', ['reviewer_name' => 'Forged name', 'rating' => 4, 'comment' => 'Updated experience.', 'is_visible' => true])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('testimonials', 1);
        $this->assertFalse($review->fresh()->is_visible);
        $this->assertSame('Member author', $review->fresh()->reviewer_name);
        $this->assertSame(0, $catalog->fresh()->reviews_count);
        $this->put('/admin/testimonials/'.$review->id, ['is_visible' => 1])->assertForbidden();
    }

    public function test_invalid_spam_admin_and_inactive_catalog_reviews_are_rejected(): void
    {
        $catalog = $this->catalog();
        $this->post('/catalog/test-template/testimonials', ['rating' => 6, 'comment' => '     '])->assertSessionHasErrors(['reviewer_name', 'rating', 'comment']);
        $this->post('/catalog/test-template/testimonials', ['reviewer_name' => 'Bot', 'rating' => 5, 'comment' => 'Spam message.', 'website' => 'spam'])->assertSessionHasErrors('website');
        $this->actingAs(User::factory()->create(['is_admin' => true]))->post('/catalog/test-template/testimonials', ['rating' => 5, 'comment' => 'Admin review.'])->assertForbidden();
        $catalog->update(['is_active' => false]);
        $this->get('/catalog/test-template')->assertNotFound();
        $this->assertDatabaseCount('testimonials', 0);
    }

    public function test_statistics_are_weighted_and_comments_are_escaped(): void
    {
        $catalog = $this->catalog();
        foreach ([5, 5, 2] as $rating) {
            Testimonial::create(['catalog_id' => $catalog->id, 'reviewer_name' => 'Guest', 'rating' => $rating, 'comment' => '<script>alert(1)</script>', 'is_visible' => true]);
        }
        Testimonial::create(['catalog_id' => $catalog->id, 'reviewer_name' => 'Hidden', 'rating' => 1, 'comment' => 'Hidden review.', 'is_visible' => false]);
        $stats = app(TestimonialStats::class)->forCatalog($catalog);
        $this->assertSame(4.0, $stats['average']);
        $this->assertSame(3, $stats['total']);
        $this->get('/catalog/test-template')->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('Hidden review.');
    }
}
