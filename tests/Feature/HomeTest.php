<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_is_public_and_uses_configured_contact(): void
    {
        DB::table('site_settings')->updateOrInsert(['key' => 'support_email'], ['value' => 'studio@example.com']);
        Cache::forget('membership.public-settings');
        $this->get('/')->assertOk()->assertSee('mailto:studio@example.com', false)->assertSee(route('login'));
    }

    public function test_logged_in_members_can_visit_home_without_exposing_private_data(): void
    {
        $member = User::factory()->create(['name' => 'Private Member', 'email' => 'private@example.com']);
        $this->actingAs($member)->get('/')->assertOk()->assertSee(route('membership.profile'))->assertDontSee('private@example.com')->assertDontSee('Private Member');
        $this->get('/profile')->assertOk();
    }

    public function test_guest_auth_pages_use_cat_logo_and_link_back_home(): void
    {
        $this->get('/login')->assertOk()->assertSee('Untitled27_20261014014436.png')->assertSee(route('home'))->assertDontSee('Daftar sekarang');
        $this->get('/register')->assertNotFound();
    }

    public function test_public_pages_share_home_and_tracking_navigation_without_registration_links(): void
    {
        foreach (['/' => 'Home', '/track' => 'Tracking'] as $path => $active) {
            $response = $this->get($path)->assertOk()->assertDontSee('/register');
            $document = new \DOMDocument;
            @$document->loadHTML($response->getContent());
            $xpath = new \DOMXPath($document);
            $links = $xpath->query('//nav[@aria-label="Navigasi utama"]/a');
            $this->assertSame(3, $links->length);
            $this->assertSame(['Home', 'Katalog', 'Tracking'], array_map(fn ($link) => trim($link->textContent), iterator_to_array($links)));
            $this->assertSame($active, trim($xpath->query('//nav/a[@aria-current="page"]')->item(0)->textContent));
        }
    }
}
