<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Catalog;
use App\Models\MemberTracking;
use App\Models\TrackingStatus;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitorLocalizationTest extends TestCase
{
    use RefreshDatabase;

    private function catalog(): Catalog
    {
        return Catalog::create(['name' => 'Original catalog name', 'slug' => 'original-catalog', 'description' => 'Deskripsi asli dari admin.', 'base_price' => 100000, 'estimated_days' => 3, 'preview_url' => 'https://example.com/demo', 'is_active' => true]);
    }

    public function test_country_takes_priority_over_browser_language_and_is_resolved_per_request(): void
    {
        config(['app.use_vercel_geolocation' => true]);
        $this->withHeaders(['X-Vercel-IP-Country' => 'ID', 'Accept-Language' => 'en-US'])->get('/')
            ->assertOk()->assertHeader('Content-Language', 'id')->assertSee('<html lang="id">', false)->assertSee('Temukan layananmu');
        $this->withHeaders(['X-Vercel-IP-Country' => 'US', 'Accept-Language' => 'id-ID'])->get('/')
            ->assertOk()->assertHeader('Content-Language', 'en')->assertSee('<html lang="en">', false)->assertSee('Find your service')->assertSee('A website that grows with your idea.')->assertDontSee('>Temukan layananmu <', false);
        $this->withHeader('X-Vercel-IP-Country', 'ID')->get('/')->assertOk()->assertSee('Temukan layananmu');
    }

    public function test_missing_country_falls_back_to_browser_language_then_indonesian(): void
    {
        config(['app.use_vercel_geolocation' => true]);
        $this->withHeader('Accept-Language', '')->get('/')->assertOk()->assertHeader('Content-Language', 'id');
        $this->withHeader('Accept-Language', 'en-US,en;q=0.9')->get('/')->assertOk()->assertHeader('Content-Language', 'en');
        $this->withHeaders(['X-Vercel-IP-Country' => 'unknown', 'Accept-Language' => 'id-ID'])->get('/')->assertOk()->assertHeader('Content-Language', 'id');
        config(['app.use_vercel_geolocation' => false]);
        $this->withHeaders(['X-Vercel-IP-Country' => 'US', 'Accept-Language' => 'id-ID'])->get('/')->assertOk()->assertHeader('Content-Language', 'id');
    }

    public function test_member_profile_orders_security_and_bills_use_english_abroad(): void
    {
        config(['app.use_vercel_geolocation' => true]);
        $member = User::factory()->create(['password_setup_at' => now()]);
        $this->actingAs($member)->withHeader('X-Vercel-IP-Country', 'GB');
        $this->get('/profile')->assertOk()->assertSee('Profile &amp; points', false)->assertSee('Active member')->assertSee('Points used')->assertSee($member->name)->assertDontSee('>Kenali profilmu.', false);
        $this->get('/tracking')->assertOk()->assertSee('My orders')->assertSee('Your journey starts here');
        $this->get('/account/security')->assertOk()->assertSee('Account security')->assertSee('Change password');
        $this->get('/bills')->assertOk()->assertSee('My bills')->assertSee('All payments');
    }

    public function test_member_order_details_and_bill_translate_interface_but_preserve_order_content(): void
    {
        config(['app.use_vercel_geolocation' => true]);
        $member = User::factory()->create(['password_setup_at' => now()]);
        $tracking = MemberTracking::create(['user_id' => $member->id, 'tracking_status_id' => TrackingStatus::firstOrFail()->id, 'reference' => 'LOCALIZED-ORDER', 'title' => 'Pesanan asli pelanggan']);
        $bill = Bill::create(['user_id' => $member->id, 'member_tracking_id' => $tracking->id, 'reference' => 'LOCALIZED-BILL', 'title' => 'Tagihan asli pelanggan', 'quantity' => 1, 'unit_price' => 100000, 'discount' => 0, 'total' => 100000]);
        $this->actingAs($member)->withHeader('X-Vercel-IP-Country', 'US')->get('/tracking/'.$tracking->id)->assertOk()->assertSee('Order details')->assertSee('Pesanan asli pelanggan')->assertSee('Billing information')->assertSee('Unpaid')->assertSee('Tagihan asli pelanggan');
        $this->get('/bills/'.$bill->id)->assertOk()->assertSee('Bill details')->assertSee('Billed to')->assertSee('Total due');
    }

    public function test_admin_stays_indonesian_and_english_login_errors_are_localized(): void
    {
        config(['app.use_vercel_geolocation' => true]);
        $this->withHeader('X-Vercel-IP-Country', 'US')->get('/login')->assertOk()->assertSee('Welcome back.');
        $this->post('/login', ['email' => 'missing@example.com', 'password' => 'wrong'])->assertSessionHasErrors(['email' => 'The email or password is incorrect.']);
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get('/admin')->assertOk()->assertHeader('Content-Language', 'id');
    }

    public function test_new_badge_is_localized_in_list_detail_and_ajax_results(): void
    {
        $this->freezeTime();
        config(['app.use_vercel_geolocation' => true]);
        $catalog = $this->catalog();
        $this->withHeader('X-Vercel-IP-Country', 'ID')->get('/catalog')->assertOk()->assertSee('catalog-new-badge')->assertSee('Baru');
        $this->withHeader('X-Vercel-IP-Country', 'US')->get('/catalog')->assertOk()->assertSee('New')->assertSee($catalog->name)->assertSee($catalog->description);
        $this->get('/catalog/'.$catalog->slug)->assertOk()->assertSee('New')->assertSee('View live demo', false);
        $this->getJson('/catalog', ['X-Catalog-Fragment' => 'results'])->assertOk()->assertJsonPath('html', fn ($html) => str_contains($html, '>New</span>'));
    }

    public function test_new_badge_expires_after_one_calendar_month_and_editing_does_not_refresh_it(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-01-31 12:00:00'));
        $catalog = $this->catalog();
        $this->travelTo(CarbonImmutable::parse('2026-02-28 11:59:59'));
        $this->assertTrue($catalog->fresh()->isNew());
        $this->travelTo(CarbonImmutable::parse('2026-02-28 12:00:00'));
        $this->assertFalse($catalog->fresh()->isNew());
        $catalog->update(['name' => 'Edited catalog']);
        $this->assertFalse($catalog->fresh()->isNew());
        $this->get('/catalog')->assertOk()->assertDontSee('catalog-new-badge');
        $this->get('/catalog/'.$catalog->slug)->assertOk()->assertDontSee('catalog-new-badge');
        $this->travelBack();
    }
}
