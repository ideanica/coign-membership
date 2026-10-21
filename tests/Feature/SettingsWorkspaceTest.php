<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SettingsWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private function staff(bool $superadmin = true): User
    {
        return User::factory()->create(['is_admin' => true, 'is_superadmin' => $superadmin]);
    }

    private function setting(string $key): string
    {
        return (string) DB::table('site_settings')->where('key', $key)->value('value');
    }

    public function test_settings_are_grouped_with_separate_dialogs_and_no_inline_edit_form(): void
    {
        $response = $this->actingAs($this->staff())->get('/admin/settings')->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);

        $this->assertSame(0, $xpath->query('//main[@id="main"]//form[not(ancestor::dialog)]')->length);
        foreach (['identity', 'contact', 'points'] as $section) {
            $this->assertSame(1, $xpath->query('//dialog[@id="settings-'.$section.'-dialog"]//form')->length);
        }
        $response->assertSee(route('admin.point-rules.index'))->assertSee(route('admin.settings.profile.update'));
    }

    public function test_identity_dialog_changes_only_the_brand_and_refreshes_cached_settings(): void
    {
        $this->actingAs($this->staff());
        $originalEmail = $this->setting('support_email');
        $originalRatio = $this->setting('rupiah_per_point');
        $this->get('/admin/settings')->assertOk();

        $this->put('/admin/settings', [
            '_settings_section' => 'identity', 'brand_name' => 'Updated Studio',
            'support_email' => 'ignored@example.com', 'rupiah_per_point' => 7,
        ])->assertSessionHasNoErrors()->assertRedirect('/admin/settings');

        $this->assertSame('Updated Studio', $this->setting('brand_name'));
        $this->assertSame($originalEmail, $this->setting('support_email'));
        $this->assertSame($originalRatio, $this->setting('rupiah_per_point'));
        $this->get('/admin/settings')->assertOk()->assertViewHas('settings', fn ($settings) => $settings['brand_name'] === 'Updated Studio');
    }

    public function test_contact_dialog_can_update_and_clear_contact_fields_without_changing_other_settings(): void
    {
        $this->actingAs($this->staff());
        $originalBrand = $this->setting('brand_name');
        $originalRatio = $this->setting('rupiah_per_point');

        $this->put('/admin/settings', [
            '_settings_section' => 'contact', 'support_email' => 'help@example.com',
            'support_whatsapp' => '+6281234567890', 'support_x' => '@studio',
        ])->assertSessionHasNoErrors()->assertRedirect('/admin/settings');

        $this->assertSame('help@example.com', $this->setting('support_email'));
        $this->assertSame('+6281234567890', $this->setting('support_whatsapp'));
        $this->put('/admin/settings', [
            '_settings_section' => 'contact', 'support_email' => '', 'support_whatsapp' => '', 'support_x' => '',
        ])->assertSessionHasNoErrors()->assertRedirect('/admin/settings');
        $this->assertSame('', $this->setting('support_email'));
        $this->assertSame('', $this->setting('support_whatsapp'));
        $this->assertSame('', $this->setting('support_x'));
        $this->assertSame($originalBrand, $this->setting('brand_name'));
        $this->assertSame($originalRatio, $this->setting('rupiah_per_point'));
    }

    public function test_superadmin_can_change_ratio_independently_without_rewriting_point_history(): void
    {
        $this->actingAs($this->staff());
        $originalBrand = $this->setting('brand_name');

        $this->put('/admin/settings', ['_settings_section' => 'points', 'rupiah_per_point' => 2500])
            ->assertSessionHasNoErrors()->assertRedirect('/admin/settings');

        $this->assertSame('2500', $this->setting('rupiah_per_point'));
        $this->assertSame($originalBrand, $this->setting('brand_name'));
        $this->assertDatabaseCount('point_entries', 0);
    }

    public function test_regular_admin_cannot_view_point_controls_or_submit_point_settings(): void
    {
        $this->actingAs($this->staff(false));

        $this->get('/admin/settings')->assertOk()->assertDontSee('id="settings-points-dialog"', false)
            ->assertDontSee('href="'.route('admin.point-rules.index').'"', false);
        $this->put('/admin/settings', ['_settings_section' => 'points'])->assertForbidden();
        $this->put('/admin/settings', ['_settings_section' => 'identity', 'brand_name' => 'Forged', 'rupiah_per_point' => 1])->assertForbidden();
        $this->assertNotSame('Forged', $this->setting('brand_name'));
    }

    public function test_invalid_contacts_do_not_partially_save_valid_values(): void
    {
        $this->actingAs($this->staff());
        $originalEmail = $this->setting('support_email');
        $originalWhatsapp = $this->setting('support_whatsapp');

        $this->put('/admin/settings', [
            '_settings_section' => 'contact', 'support_email' => 'not-an-email', 'support_whatsapp' => '+6281234567890', 'support_x' => 'https://example.com',
        ])->assertSessionHasErrors(['support_email', 'support_x']);

        $this->assertSame($originalEmail, $this->setting('support_email'));
        $this->assertSame($originalWhatsapp, $this->setting('support_whatsapp'));
    }

    public function test_invalid_section_or_ratio_is_rejected_without_changing_data(): void
    {
        $this->actingAs($this->staff());
        $originalRatio = $this->setting('rupiah_per_point');

        $this->put('/admin/settings', ['_settings_section' => 'unknown', 'brand_name' => 'Forged'])->assertSessionHasErrors('_settings_section');
        $this->put('/admin/settings', ['_settings_section' => 'points', 'rupiah_per_point' => 0])->assertSessionHasErrors('rupiah_per_point');

        $this->assertSame($originalRatio, $this->setting('rupiah_per_point'));
    }

    public function test_validation_reopens_the_correct_dialog_and_preserves_input(): void
    {
        $this->actingAs($this->staff());

        $this->followingRedirects()->from('/admin/settings')->put('/admin/settings', [
            '_settings_section' => 'contact', 'support_email' => 'invalid-email', 'support_whatsapp' => '', 'support_x' => '',
        ])->assertOk()->assertSee('data-open-on-error', false)->assertSee('invalid-email');
    }
}
