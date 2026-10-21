<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavbarConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_navigation_stays_consistent_across_public_and_member_pages(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['/', '/catalog', '/track', '/profile', '/tracking'] as $path) {
            $response = $this->get($path)->assertOk();
            $document = new \DOMDocument;
            @$document->loadHTML($response->getContent());
            $xpath = new \DOMXPath($document);
            $links = $xpath->query('//nav[@id="site-navigation"]/a');

            $this->assertSame(['Home', 'Katalog', 'Profil & poin', 'Pesanan'], array_map(fn ($link) => trim($link->textContent), iterator_to_array($links)), $path);
            $this->assertSame('IT Creative Workspace', trim($xpath->query('//header//span[contains(@class,"brand-caption")]')->item(0)->textContent));
            $this->assertSame(route('logout'), $xpath->query('//div[@id="site-account-actions"]/form')->item(0)->getAttribute('action'));
        }
    }

    public function test_admin_can_reach_admin_panel_from_public_and_member_navigation(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        foreach (['/', '/catalog', '/profile', '/tracking'] as $path) {
            $this->get($path)->assertOk()->assertSee('href="'.route('admin.dashboard').'"', false);
        }
    }
}
