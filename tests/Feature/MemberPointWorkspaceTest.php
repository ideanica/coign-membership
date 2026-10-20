<?php

namespace Tests\Feature;

use App\Models\PointRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberPointWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private function xpath(string $html): \DOMXPath
    {
        $document = new \DOMDocument;
        @$document->loadHTML($html);

        return new \DOMXPath($document);
    }

    public function test_member_forms_are_in_dialogs_with_unique_field_ids(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create(['name' => 'Member untuk diedit']);

        $response = $this->actingAs($admin)->get('/admin/members')->assertOk();
        $xpath = $this->xpath($response->getContent());

        $this->assertSame(1, $xpath->query('//dialog[@id="member-create-dialog"]//form')->length);
        $this->assertSame(1, $xpath->query('//dialog[@id="member-edit-dialog"]//form')->length);
        $this->assertSame(0, $xpath->query('//input[@type="password" and not(ancestor::dialog)]')->length);
        $ids = array_map(fn ($node) => $node->getAttribute('id'), iterator_to_array($xpath->query('//*[@id]')));
        $this->assertCount(count(array_unique($ids)), $ids);
        $response->assertSee('data-form-action="'.route('admin.members.update', $member).'"', false);
    }

    public function test_member_filter_preserves_overview_counts_and_excludes_admins(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->create(['is_active' => true]);
        $inactive = User::factory()->create(['is_active' => false]);

        $this->actingAs($admin)->get('/admin/members?access=inactive')->assertOk()
            ->assertViewHas('members', fn ($rows): bool => $rows->modelKeys() === [$inactive->id])
            ->assertViewHas('memberStats', ['total' => 2, 'active' => 1, 'inactive' => 1]);
    }

    public function test_invalid_member_creation_reopens_the_modal_without_exposing_passwords(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->followingRedirects()->from(route('admin.members.index'))
            ->post(route('admin.members.store'), ['_workspace_form' => 'member-create', 'name' => '', 'email' => 'retained@example.com', 'password' => 'secret-password', 'password_confirmation' => 'different', 'member_tier' => 'Gold', 'is_active' => 1])
            ->assertOk()->assertSee('retained@example.com')->assertDontSee('secret-password');

        $this->assertSame(1, $this->xpath($response->getContent())->query('//dialog[@id="member-create-dialog" and @data-open-on-error]')->length);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_invalid_member_edit_restores_the_correct_account_in_the_modal(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create();

        $response = $this->actingAs($admin)->followingRedirects()->from(route('admin.members.index'))
            ->put(route('admin.members.update', $member), ['_workspace_form' => 'member-edit', '_workspace_id' => $member->id, 'name' => 'Nama belum tersimpan', 'email' => 'invalid', 'member_tier' => 'Gold', 'is_active' => 1])->assertOk();
        $xpath = $this->xpath($response->getContent());

        $this->assertSame(1, $xpath->query('//dialog[@id="member-edit-dialog" and @data-open-on-error]//form[@action="'.route('admin.members.update', $member).'"]')->length);
        $this->assertSame('Nama belum tersimpan', $xpath->query('//*[@id="member-edit-name"]')->item(0)->getAttribute('value'));
        $this->assertDatabaseHas('users', ['id' => $member->id, 'name' => $member->name]);
    }

    public function test_point_filters_scope_history_and_summary_to_the_selected_member(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create();
        $other = User::factory()->create();
        $earned = $member->pointEntries()->create(['reference' => 'BONUS-LOCAL', 'type' => 'earned', 'points' => 100, 'title' => 'Bonus lokal', 'category' => 'Bonus', 'occurred_at' => '2026-10-14']);
        $member->pointEntries()->create(['reference' => 'REDEEM-LOCAL', 'type' => 'redeemed', 'points' => 30, 'title' => 'Penggunaan', 'category' => 'Voucher', 'occurred_at' => '2026-10-14']);
        $other->pointEntries()->create(['reference' => 'BONUS-OTHER', 'type' => 'earned', 'points' => 900, 'title' => 'Bonus lain', 'category' => 'Bonus', 'occurred_at' => '2026-10-14']);

        $this->actingAs($admin)->get('/admin/points?member='.$member->id.'&type=earned&q=Bonus')->assertOk()
            ->assertViewHas('entries', fn ($rows): bool => $rows->modelKeys() === [$earned->id])
            ->assertViewHas('summary', ['earned' => 100, 'used' => 30]);
    }

    public function test_insufficient_points_reopen_adjustment_dialog_without_writing_a_ledger_entry(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create();

        $response = $this->actingAs($admin)->followingRedirects()->from(route('admin.points.index'))
            ->post(route('admin.points.store'), ['_workspace_form' => 'point-adjustment', 'user_id' => $member->id, 'type' => 'redeemed', 'points' => 10, 'reason' => 'Koreksi yang belum tersimpan'])->assertOk()->assertSee('Saldo poin tidak mencukupi.');

        $this->assertSame(1, $this->xpath($response->getContent())->query('//dialog[@id="point-adjustment-dialog" and @data-open-on-error]')->length);
        $this->assertDatabaseCount('point_entries', 0);
    }

    public function test_rules_are_sorted_and_editing_uses_one_reusable_dialog(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        PointRule::create(['name' => 'Batas tinggi', 'minimum_amount' => 200000, 'points' => 100, 'is_active' => true]);
        PointRule::create(['name' => 'Batas rendah', 'minimum_amount' => 100000, 'points' => 50, 'is_active' => true]);

        $response = $this->actingAs($admin)->get('/admin/point-rules')->assertOk()->assertSeeInOrder(['Batas rendah', 'Batas tinggi']);
        $xpath = $this->xpath($response->getContent());

        $this->assertSame(1, $xpath->query('//dialog[@id="rule-edit-dialog"]//form')->length);
        $this->assertSame(0, $xpath->query('//tbody//input[@type="number"]')->length);
        $this->assertSame(1, $xpath->query('//dialog[@id="point-purchase-dialog"]//form')->length);
    }

    public function test_invalid_rule_edit_preserves_the_target_and_reopens_its_dialog(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $rule = PointRule::create(['name' => 'Aturan awal', 'minimum_amount' => 100000, 'points' => 50, 'is_active' => true]);

        $response = $this->actingAs($admin)->followingRedirects()->from(route('admin.point-rules.index'))
            ->put(route('admin.point-rules.update', $rule), ['_workspace_form' => 'rule-edit', '_workspace_id' => $rule->id, 'name' => 'Perubahan belum tersimpan', 'minimum_amount' => 0, 'points' => 50])->assertOk();
        $xpath = $this->xpath($response->getContent());

        $this->assertSame(1, $xpath->query('//dialog[@id="rule-edit-dialog" and @data-open-on-error]//form[@action="'.route('admin.point-rules.update', $rule).'"]')->length);
        $this->assertSame('Perubahan belum tersimpan', $xpath->query('//*[@id="rule-edit-name"]')->item(0)->getAttribute('value'));
        $this->assertDatabaseHas('point_rules', ['id' => $rule->id, 'minimum_amount' => 100000]);
    }
}
