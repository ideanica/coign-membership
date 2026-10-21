<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\MemberTracking;
use App\Models\PointRule;
use App\Models\TrackingStatus;
use App\Models\User;
use App\Services\BillPayment;
use App\Services\TrackingWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicTrackingAndAutomaticPointsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true, 'is_superadmin' => true])->save();

        return $admin;
    }

    private function order(User $admin, ?User $member = null, bool $public = true): MemberTracking
    {
        return app(TrackingWorkflow::class)->create(['user_id' => $member?->id, 'title' => 'Proyek website pelanggan', 'description' => 'Private project description', 'tracking_status_id' => TrackingStatus::where('is_final', false)->first()->id, 'public_enabled' => $public], $admin, 'Private member note');
    }

    private function purchase(User $member, string $reference, int $amount): array
    {
        return ['user_id' => $member->id, 'reference' => $reference, 'title' => 'Bantuan tugas mobile', 'category' => 'Pemrograman', 'amount' => $amount, 'payment' => 'Transfer bank'];
    }

    public function test_guest_can_track_order_without_exposing_private_member_data(): void
    {
        $member = User::factory()->create(['name' => 'Private Customer', 'email' => 'private@example.com']);
        $order = $this->order($this->admin(), $member);
        $this->get('/track')->assertOk();
        $this->post('/track', ['code' => strtolower($order->public_code)])
            ->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('Proyek website pelanggan')->assertSee($order->status->label)
            ->assertDontSee('private@example.com')->assertDontSee('Private Customer')
            ->assertDontSee('Private project description')->assertDontSee('Private member note')
            ->assertDontSee($order->reference);
    }

    public function test_disabled_unknown_and_invalid_codes_do_not_expose_orders(): void
    {
        $order = $this->order($this->admin(), null, false);
        foreach ([$order->public_code, 'ORD-AAAAAAAAAAAAAAAA'] as $code) {
            $this->post('/track', ['code' => $code])->assertOk()->assertSee('Pesanan belum ditemukan')->assertDontSee($order->title);
        }
        $this->post('/track', ['code' => $order->reference])->assertSessionHasErrors('code');
        $this->post('/track', ['code' => ['malformed']])->assertSessionHasErrors('code');
    }

    public function test_member_can_copy_and_search_the_same_code_accepted_by_public_tracking(): void
    {
        $member = User::factory()->create();
        $order = $this->order($this->admin(), $member);

        $this->actingAs($member)->get('/tracking/'.$order->id)
            ->assertOk()->assertSee('data-copy-reference="'.$order->public_code.'"', false);
        $this->get('/tracking?q='.$order->public_code)->assertOk()->assertSee($order->title);
        $this->post('/track', ['code' => $order->public_code])->assertOk()->assertSee($order->title);
    }

    public function test_searching_a_public_code_does_not_expose_another_members_order(): void
    {
        $order = $this->order($this->admin(), User::factory()->create());

        $this->actingAs(User::factory()->create())->get('/tracking?q='.$order->public_code)
            ->assertOk()->assertDontSee($order->title)->assertSee('Tracking belum ditemukan');
    }

    public function test_admin_can_find_an_order_using_its_public_code(): void
    {
        $admin = $this->admin();
        $order = $this->order($admin);

        $this->actingAs($admin)->get('/admin/tracking?q='.$order->public_code)
            ->assertOk()->assertSee($order->title);
    }

    public function test_member_is_informed_when_public_tracking_is_disabled(): void
    {
        $member = User::factory()->create();
        $order = $this->order($this->admin(), $member, false);

        $this->actingAs($member)->get('/tracking/'.$order->id)
            ->assertOk()->assertSee('Tracking publik belum aktif')
            ->assertDontSee('data-copy-reference="'.$order->public_code.'"', false);
    }

    public function test_admin_can_create_guest_order_and_revoke_public_access(): void
    {
        $admin = $this->admin();
        $values = ['title' => 'Guest order', 'tracking_status_id' => TrackingStatus::first()->id, 'public_enabled' => '1', 'customer_name' => 'Guest customer', 'quantity' => 1, 'unit_price' => 100000, 'discount' => 0];
        $this->actingAs($admin)->post('/admin/tracking', $values)->assertSessionHasNoErrors()->assertRedirect();
        $order = MemberTracking::firstOrFail();
        $this->assertNull($order->user_id);
        $this->assertMatchesRegularExpression('/^ORD-[A-Z0-9]{16}$/', $order->public_code);
        foreach (['/admin', '/admin/tracking', '/admin/tracking/'.$order->id.'/edit'] as $path) {
            $this->get($path)->assertOk();
        }
        $this->put('/admin/tracking/'.$order->id, [...$values, 'public_enabled' => '0'])->assertSessionHasNoErrors();
        $this->post('/track', ['code' => $order->public_code])->assertDontSee('Guest order')->assertSee('Pesanan belum ditemukan');
    }

    public function test_tiers_apply_highest_qualifying_rule_at_inclusive_boundary_and_do_not_stack(): void
    {
        $admin = $this->admin();
        $member = User::factory()->create();
        $this->actingAs($admin);
        foreach ([[100000, 50], [250000, 150]] as [$minimum, $points]) {
            $this->post('/admin/point-rules', ['name' => 'Bonus '.$minimum, 'minimum_amount' => $minimum, 'points' => $points, 'is_active' => 1])->assertSessionHasNoErrors();
        }
        $this->post('/admin/point-rules/mode', ['points_mode' => 'tiers'])->assertSessionHasNoErrors();
        foreach ([[99999, 0], [100000, 50], [249999, 50], [250000, 150], [900000, 150]] as $index => [$amount, $expected]) {
            $reference = 'PUR-TEST-'.$index;
            $this->post('/admin/point-rules/purchase', $this->purchase($member, $reference, $amount))->assertSessionHasNoErrors();
            $transaction = $member->membershipTransactions()->where('reference', $reference)->firstOrFail();
            $this->assertSame($expected, $transaction->pointEntry?->points ?? 0);
            if ($expected > 0) {
                $this->assertSame($admin->id, $transaction->pointEntry->actor_id);
                $this->assertStringContainsString('Aturan Bonus', $transaction->pointEntry->description);
            }
        }
        $this->get('/admin/point-rules')->assertOk();
    }

    public function test_rule_changes_only_affect_future_purchases_and_duplicate_purchase_is_rejected(): void
    {
        $admin = $this->admin();
        $member = User::factory()->create();
        $rule = PointRule::create(['name' => 'First tier', 'minimum_amount' => 100000, 'points' => 60, 'is_active' => true]);
        $this->actingAs($admin)->post('/admin/point-rules/mode', ['points_mode' => 'tiers']);
        $values = $this->purchase($member, 'PUR-UNIQUE', 100000);
        $this->post('/admin/point-rules/purchase', $values)->assertSessionHasNoErrors();
        $this->post('/admin/point-rules/purchase', $values)->assertSessionHasErrors('reference');
        $this->assertDatabaseCount('membership_transactions', 1);
        $this->assertDatabaseCount('point_entries', 1);
        $this->put('/admin/point-rules/'.$rule->id, ['name' => 'Changed tier', 'minimum_amount' => 100000, 'points' => 90, 'is_active' => 1])->assertSessionHasNoErrors();
        $this->post('/admin/point-rules/purchase', $this->purchase($member, 'PUR-NEXT', 100000))->assertSessionHasNoErrors();
        $this->assertSame(60, $member->membershipTransactions()->where('reference', 'PUR-UNIQUE')->first()->pointEntry->points);
        $this->assertSame(90, $member->membershipTransactions()->where('reference', 'PUR-NEXT')->first()->pointEntry->points);
        $this->delete('/admin/point-rules/'.$rule->id)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('point_entries', 2);
    }

    public function test_paid_bill_uses_tier_once_and_inactive_rules_are_ignored(): void
    {
        $admin = $this->admin();
        $member = User::factory()->create();
        PointRule::create(['name' => 'Active', 'minimum_amount' => 100000, 'points' => 75, 'is_active' => true]);
        PointRule::create(['name' => 'Inactive', 'minimum_amount' => 200000, 'points' => 999, 'is_active' => false]);
        $this->actingAs($admin)->post('/admin/point-rules/mode', ['points_mode' => 'tiers']);
        $bill = Bill::create(['user_id' => $member->id, 'reference' => 'BILL-TIER', 'title' => 'Bill test', 'quantity' => 1, 'unit_price' => 250000, 'total' => 250000]);
        app(BillPayment::class)->pay($bill, $admin, 'QRIS');
        app(BillPayment::class)->pay($bill, $admin, 'QRIS');
        $this->assertDatabaseCount('point_entries', 1);
        $this->assertSame(75, $bill->fresh()->transaction->pointEntry->points);
        $this->post('/admin/point-rules/purchase', $this->purchase($member, $bill->reference, 250000))->assertSessionHasErrors('reference');
    }

    public function test_members_cannot_manage_rules_or_credit_purchases_and_invalid_rules_are_rejected(): void
    {
        $member = User::factory()->create();
        $this->actingAs($member)->get('/admin/point-rules')->assertForbidden();
        $this->post('/admin/point-rules/purchase', $this->purchase($member, 'PUR-FORBIDDEN', 100000))->assertForbidden();
        $this->actingAs($this->admin())->post('/admin/point-rules', ['name' => 'Bad', 'minimum_amount' => -1, 'points' => 0])->assertSessionHasErrors(['minimum_amount', 'points']);
        $rule = PointRule::create(['name' => 'Existing', 'minimum_amount' => 100000, 'points' => 10]);
        $this->post('/admin/point-rules', ['name' => 'Duplicate', 'minimum_amount' => $rule->minimum_amount, 'points' => 20])->assertSessionHasErrors('minimum_amount');
    }
}
