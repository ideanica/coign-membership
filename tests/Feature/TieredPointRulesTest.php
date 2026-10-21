<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\PointRule;
use App\Models\User;
use App\Services\AutomaticPoints;
use App\Services\BillPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TieredPointRulesTest extends TestCase
{
    use RefreshDatabase;

    private function rule(array $overrides = []): array
    {
        return array_replace(['name' => 'Tier A', 'minimum_amount' => 50000, 'maximum_amount' => 69999, 'reward_type' => 'fixed', 'points' => 5000, 'is_active' => 1], $overrides);
    }

    private function superadmin(): User
    {
        return User::factory()->create(['is_admin' => true, 'is_superadmin' => true]);
    }

    private function tiers(): void
    {
        $this->post('/admin/point-rules/mode', ['points_mode' => 'tiers'])->assertSessionHasNoErrors();
    }

    public function test_adjacent_ranges_apply_one_award_at_both_inclusive_boundaries(): void
    {
        $this->actingAs($this->superadmin());
        $this->post('/admin/point-rules', $this->rule())->assertSessionHasNoErrors();
        $this->post('/admin/point-rules', $this->rule(['name' => 'Tier B', 'minimum_amount' => 70000, 'maximum_amount' => null, 'points' => 7000]))->assertSessionHasNoErrors();
        $this->tiers();
        foreach ([49999 => 0, 50000 => 5000, 69999 => 5000, 70000 => 7000, 80000 => 7000] as $amount => $points) {
            $this->assertSame($points, app(AutomaticPoints::class)->award($amount)['points']);
        }
    }

    public function test_overlap_containment_shared_boundary_and_unbounded_conflicts_are_rejected(): void
    {
        $this->actingAs($this->superadmin());
        $this->post('/admin/point-rules', $this->rule())->assertSessionHasNoErrors();
        foreach ([[60000, 80000], [40000, 55000], [40000, 80000], [55000, 60000], [69999, 80000], [40000, null]] as [$minimum, $maximum]) {
            $this->post('/admin/point-rules', $this->rule(['minimum_amount' => $minimum, 'maximum_amount' => $maximum]))->assertSessionHasErrors('minimum_amount');
        }
        $this->assertDatabaseCount('point_rules', 1);
    }

    public function test_inactive_overlap_can_be_saved_but_cannot_be_activated_until_resolved(): void
    {
        $this->actingAs($this->superadmin());
        $this->post('/admin/point-rules', $this->rule())->assertSessionHasNoErrors();
        $inactive = $this->rule(['name' => 'Inactive', 'minimum_amount' => 60000, 'maximum_amount' => 80000, 'is_active' => 0]);
        $this->post('/admin/point-rules', $inactive)->assertSessionHasNoErrors();
        $rule = PointRule::where('name', 'Inactive')->firstOrFail();
        $this->put('/admin/point-rules/'.$rule->id, [...$inactive, 'is_active' => 1])->assertSessionHasErrors('minimum_amount');
        $this->assertFalse($rule->fresh()->is_active);
        $this->put('/admin/point-rules/'.$rule->id, [...$inactive, 'minimum_amount' => 70000, 'is_active' => 1])->assertSessionHasNoErrors();
        $this->assertTrue($rule->fresh()->is_active);
    }

    public function test_gaps_and_exact_amount_rules_award_zero_outside_their_ranges(): void
    {
        $this->actingAs($this->superadmin());
        $this->post('/admin/point-rules', $this->rule(['maximum_amount' => 50000]))->assertSessionHasNoErrors();
        $this->tiers();
        foreach ([49999 => 0, 50000 => 5000, 50001 => 0] as $amount => $expected) {
            $this->assertSame($expected, app(AutomaticPoints::class)->award($amount)['points']);
        }
    }

    public function test_multiple_rewards_round_down_use_total_amount_and_respect_cap(): void
    {
        $this->actingAs($this->superadmin());
        $this->post('/admin/point-rules', $this->rule(['maximum_amount' => null, 'reward_type' => 'multiple', 'step_amount' => 10000, 'points' => 500, 'maximum_points' => 4500]))->assertSessionHasNoErrors();
        $this->tiers();
        foreach ([49999 => 0, 50000 => 2500, 79999 => 3500, 80000 => 4000, 120000 => 4500, 10000000000000 => 4500] as $amount => $expected) {
            $this->assertSame($expected, app(AutomaticPoints::class)->award($amount)['points']);
        }
        $this->get('/admin/point-rules')->assertOk()->assertSee('per Rp10.000')->assertSee('data-reward-type', false);
    }

    public function test_default_multiple_limit_prevents_integer_overflow_and_fixed_mode_clears_formula_fields(): void
    {
        $this->actingAs($this->superadmin());
        $payload = $this->rule(['maximum_amount' => null, 'reward_type' => 'multiple', 'step_amount' => 1, 'points' => 100000000]);
        $this->post('/admin/point-rules', $payload)->assertSessionHasNoErrors();
        $this->tiers();
        $this->assertSame(PointRule::MAX_POINTS, app(AutomaticPoints::class)->award(10000000000000)['points']);
        $rule = PointRule::sole();
        $this->put('/admin/point-rules/'.$rule->id, [...$payload, 'reward_type' => 'fixed'])->assertSessionHasNoErrors();
        $this->assertNull($rule->fresh()->step_amount);
        $this->assertNull($rule->fresh()->maximum_points);
    }

    public function test_invalid_range_and_formula_are_rejected_without_partial_changes(): void
    {
        $this->actingAs($this->superadmin());
        $this->post('/admin/point-rules', $this->rule(['maximum_amount' => 49999]))->assertSessionHasErrors('maximum_amount');
        $this->post('/admin/point-rules', $this->rule(['reward_type' => 'multiple']))->assertSessionHasErrors('step_amount');
        $this->post('/admin/point-rules', $this->rule(['reward_type' => 'multiple', 'step_amount' => 0]))->assertSessionHasErrors('step_amount');
        $this->post('/admin/point-rules', $this->rule(['reward_type' => 'unknown']))->assertSessionHasErrors('reward_type');
        $this->assertDatabaseCount('point_rules', 0);
    }

    public function test_bill_and_manual_purchase_share_the_formula_and_bill_retry_never_awards_twice(): void
    {
        $admin = $this->superadmin();
        $member = User::factory()->create();
        $this->actingAs($admin);
        $this->post('/admin/point-rules', $this->rule(['maximum_amount' => null, 'reward_type' => 'multiple', 'step_amount' => 10000, 'points' => 500]))->assertSessionHasNoErrors();
        $this->tiers();
        $bill = Bill::create(['user_id' => $member->id, 'reference' => 'RANGED-BILL', 'title' => 'Service', 'quantity' => 1, 'unit_price' => 80000, 'total' => 80000]);
        app(BillPayment::class)->pay($bill, $admin, 'QRIS');
        app(BillPayment::class)->pay($bill, $admin, 'QRIS');
        $this->assertSame(4000, $bill->fresh()->transaction->pointEntry->points);
        $this->post('/admin/point-rules/purchase', ['user_id' => $member->id, 'reference' => 'RANGED-PURCHASE', 'title' => 'Purchase', 'category' => 'Service', 'amount' => 80000, 'payment' => 'QRIS'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('point_entries', 2);
        $this->assertSame(8000, (int) $member->pointEntries()->sum('points'));
        $this->assertStringContainsString('per kelipatan Rp10.000', $bill->fresh()->transaction->pointEntry->description);
    }

    public function test_migration_preserves_highest_threshold_behavior_without_using_inactive_boundaries(): void
    {
        $this->actingAs($this->superadmin());
        $this->tiers();
        PointRule::create($this->rule(['maximum_amount' => null]));
        PointRule::create($this->rule(['minimum_amount' => 60000, 'maximum_amount' => null, 'is_active' => false]));
        PointRule::create($this->rule(['minimum_amount' => 70000, 'maximum_amount' => null, 'points' => 7000]));
        $migration = require database_path('migrations/2026_10_21_204025_add_ranges_and_rewards_to_point_rules_table.php');
        $migration->down();
        $migration->up();
        $this->assertSame(69999, PointRule::where('minimum_amount', 50000)->sole()->maximum_amount);
        $this->assertSame(5000, app(AutomaticPoints::class)->award(65000)['points']);
        $this->assertSame(7000, app(AutomaticPoints::class)->award(80000)['points']);
    }

    public function test_regular_admin_and_member_cannot_manage_new_rule_fields(): void
    {
        foreach ([['is_admin' => true], ['is_admin' => false]] as $attributes) {
            $this->actingAs(User::factory()->create($attributes))->post('/admin/point-rules', $this->rule())->assertForbidden();
        }
        $this->assertDatabaseCount('point_rules', 0);
    }
}
