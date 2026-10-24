<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WebDevelopmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_packages_are_public_with_the_agreed_prices_and_active_navigation(): void
    {
        $response = $this->get('/web-development')->assertOk()
            ->assertSeeInOrder(['Rp1.490.000', 'Rp1.190.000', 'Rp2.990.000', 'Rp2.390.000', 'Rp5.490.000', 'Rp4.390.000', 'Rp6.990.000', 'Rp5.590.000'])
            ->assertSee('Most Recommended')
            ->assertSee('Optional Limited Content Management')
            ->assertSee('Harga mulai tidak berarti seluruh possible features termasuk sekaligus.');

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);

        $this->assertSame('Packages', trim($xpath->query('//nav/a[@aria-current="page"]')->item(0)->textContent));
        $this->assertSame(4, $xpath->query('//s')->length);
        $this->get('/')->assertOk()->assertSee('href="'.route('web-development').'"', false);
    }

    public function test_package_inquiries_use_the_configured_whatsapp_with_the_selected_package(): void
    {
        DB::table('site_settings')->updateOrInsert(['key' => 'support_whatsapp'], ['value' => '+62 812-3456-7890']);
        Cache::forget('membership.public-settings');

        $this->get('/web-development')->assertOk()
            ->assertSee('https://wa.me/6281234567890?text='.rawurlencode('Halo, saya ingin diskusi paket Web Development Business.'), false)
            ->assertSee('Diskusikan paket Business');
    }

    public function test_inquiries_fall_back_to_the_configured_email_when_whatsapp_is_unavailable(): void
    {
        DB::table('site_settings')->updateOrInsert(['key' => 'support_whatsapp'], ['value' => '']);
        DB::table('site_settings')->updateOrInsert(['key' => 'support_email'], ['value' => 'studio@example.com']);
        Cache::forget('membership.public-settings');

        $this->get('/web-development')->assertOk()
            ->assertSee('mailto:studio@example.com?subject='.rawurlencode('Halo, saya ingin diskusi paket Web Development Starter.'), false)
            ->assertDontSee('https://wa.me/');
    }

    public function test_inquiries_link_to_existing_contact_section_when_contacts_are_not_configured(): void
    {
        DB::table('site_settings')->whereIn('key', ['support_whatsapp', 'support_email'])->delete();
        Cache::forget('membership.public-settings');

        $this->get('/web-development')->assertOk()
            ->assertSee('href="'.route('home').'#kontak"', false)
            ->assertDontSee('https://wa.me/')
            ->assertDontSee('mailto:');
    }

    public function test_faq_and_process_follow_the_visitors_language(): void
    {
        $this->withHeader('Accept-Language', 'en-US')->get('/web-development')->assertOk()
            ->assertSee('Are domain and hosting included?')
            ->assertSee('Choose the right next step for your business.')
            ->assertSee('Understand your business, target customers, and website needs.');

        $this->withHeader('Accept-Language', 'id-ID')->get('/web-development')->assertOk()
            ->assertSee('Apakah domain dan hosting sudah termasuk?');
    }
}
