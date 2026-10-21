<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\MemberTracking;
use App\Models\Service;
use App\Models\TrackingStatus;
use App\Models\User;
use App\Services\BillPayment;
use App\Services\MembershipLedger;
use App\Services\TrackingWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminPortalTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true, 'is_superadmin' => true])->save();

        return $admin;
    }

    private function tracking(User $member, User $admin): MemberTracking
    {
        return app(TrackingWorkflow::class)->create(['user_id' => $member->id, 'tracking_status_id' => TrackingStatus::where('is_final', false)->first()->id, 'title' => 'Tracking privat member', 'description' => 'Data privat', 'due_date' => '2026-11-01'], $admin, 'Pekerjaan diterima.');
    }

    private function bill(User $member): Bill
    {
        return Bill::create(['user_id' => $member->id, 'reference' => 'BILL-TEST-001', 'title' => 'Layanan pengujian', 'quantity' => 2, 'unit_price' => 200000, 'discount' => 100000, 'total' => 300000]);
    }

    public function test_members_cannot_access_or_write_admin_data(): void
    {
        $member = User::factory()->create();
        $this->actingAs($member);
        foreach (['/admin', '/admin/members', '/admin/points', '/admin/services', '/admin/statuses', '/admin/tracking', '/admin/bills', '/admin/settings'] as $path) {
            $this->get($path)->assertForbidden();
        }
        $this->post('/admin/points', ['user_id' => $member->id, 'type' => 'earned', 'points' => 999, 'reason' => 'Unauthorized'])->assertForbidden();
        $this->assertDatabaseCount('point_entries', 0);
    }

    public function test_registration_cannot_grant_admin_role(): void
    {
        $this->post('/register', ['name' => 'Member', 'email' => 'member@example.com', 'password' => 'secret-password', 'password_confirmation' => 'secret-password', 'is_admin' => true])->assertNotFound();
        $this->assertDatabaseMissing('users', ['email' => 'member@example.com']);
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_admin_and_member_pages_render_including_empty_states(): void
    {
        $this->actingAs($this->admin());
        foreach (['/admin', '/admin/members', '/admin/members/create', '/admin/points', '/admin/services', '/admin/services/create', '/admin/statuses', '/admin/statuses/create', '/admin/tracking', '/admin/tracking/create', '/admin/bills', '/admin/settings'] as $path) {
            $this->get($path)->assertOk();
        }
        $this->actingAs(User::factory()->create(['password_setup_at' => now()]));
        foreach (['/tracking', '/bills'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_member_cannot_see_another_members_tracking_or_bill(): void
    {
        $admin = $this->admin();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $tracking = $this->tracking($owner, $admin);
        $bill = $this->bill($owner);
        $this->actingAs($other)->get('/tracking/'.$tracking->id)->assertNotFound();
        $this->get('/bills/'.$bill->id)->assertNotFound();
        $this->get('/tracking')->assertDontSee('Tracking privat member');
        $this->get('/bills')->assertDontSee('BILL-TEST-001');
        $this->actingAs($owner)->get('/tracking/'.$tracking->id)->assertOk()->assertSee('Pekerjaan diterima.');
        $this->get('/bills/'.$bill->id)->assertOk()->assertSee('BILL-TEST-001');
    }

    public function test_tracking_overview_counts_only_owned_services_and_keeps_summary_when_filtered(): void
    {
        $admin = $this->admin();
        $member = User::factory()->create();
        $active = $this->tracking($member, $admin);
        $closed = $this->tracking($member, $admin);
        $closed->update(['closed_at' => now(), 'title' => 'Layanan ditutup']);
        $this->tracking(User::factory()->create(), $admin);

        $this->actingAs($member)->get('/tracking?q='.$active->reference)
            ->assertOk()
            ->assertViewHas('summary', ['total' => 2, 'active' => 1, 'closed' => 1])
            ->assertSee('Pekerjaan diterima.')
            ->assertDontSee('Layanan ditutup');
    }

    public function test_admin_member_management_preserves_history_and_cannot_promote_members(): void
    {
        $this->actingAs($this->admin())->post('/admin/members', ['name' => 'Created Member', 'email' => 'created@example.com', 'password' => 'secret-password', 'password_confirmation' => 'secret-password', 'member_tier' => 'Gold Member', 'is_active' => true, 'is_admin' => true])->assertSessionHasNoErrors()->assertRedirect('/admin/members');
        $member = User::where('email', 'created@example.com')->firstOrFail();
        $this->assertFalse($member->is_admin);
        $this->assertSame('Gold Member', $member->member_tier);
        $this->get('/admin/members/'.$member->id.'/edit')->assertOk();
        $this->put('/admin/members/'.$member->id, ['name' => 'Updated Member', 'email' => $member->email, 'member_tier' => 'Silver Member', 'is_active' => true])->assertSessionHasNoErrors();
        $this->delete('/admin/members/'.$member->id)->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $member->id, 'name' => 'Updated Member', 'is_active' => false]);
    }

    public function test_inactive_members_cannot_login_or_keep_an_active_session(): void
    {
        $member = User::factory()->create(['password' => 'secret-password']);
        $member->forceFill(['is_active' => false])->save();
        $this->post('/login', ['email' => $member->email, 'password' => 'secret-password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->actingAs($member)->get('/tracking')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_admin_point_adjustments_are_audited_and_cannot_overdraw(): void
    {
        $admin = $this->admin();
        $member = User::factory()->create();
        $this->actingAs($admin)->post('/admin/points', ['user_id' => $member->id, 'type' => 'earned', 'points' => 100, 'reason' => 'Bonus loyalitas'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('point_entries', ['user_id' => $member->id, 'actor_id' => $admin->id, 'points' => 100, 'description' => 'Bonus loyalitas']);
        $this->post('/admin/points', ['user_id' => $member->id, 'type' => 'redeemed', 'points' => 101, 'reason' => 'Koreksi'])->assertSessionHasErrors('points');
        $this->assertDatabaseCount('point_entries', 1);
        $this->post('/admin/points', ['user_id' => $member->id, 'type' => 'redeemed', 'points' => 40, 'reason' => 'Koreksi'])->assertSessionHasNoErrors();
        $this->actingAs($member)->get('/profile')->assertViewHas('summary', ['earned' => 100, 'used' => 40, 'balance' => 60]);
    }

    public function test_service_catalog_and_status_labels_can_be_managed(): void
    {
        $this->actingAs($this->admin())->post('/admin/services', ['name' => 'Creative Service', 'code' => 'CREATIVE-01', 'price' => 200000, 'delivery_days' => 3, 'is_active' => true])->assertSessionHasNoErrors();
        $service = Service::firstOrFail();
        $this->get('/admin/services/'.$service->id.'/edit')->assertOk();
        $this->put('/admin/services/'.$service->id, ['name' => 'Updated Service', 'code' => 'CREATIVE-01', 'price' => 250000, 'delivery_days' => 4, 'is_active' => true])->assertSessionHasNoErrors();
        $this->delete('/admin/services/'.$service->id)->assertRedirect();
        $this->assertDatabaseHas('services', ['id' => $service->id, 'name' => 'Updated Service', 'is_active' => false]);
        $this->post('/admin/statuses', ['label' => 'Review', 'color' => '#7a83ff', 'sort_order' => 8, 'is_final' => false, 'is_active' => true])->assertSessionHasNoErrors();
        $status = TrackingStatus::where('label', 'Review')->firstOrFail();
        $this->get('/admin/statuses/'.$status->id.'/edit')->assertOk();
        $this->delete('/admin/statuses/'.$status->id)->assertRedirect();
        $this->assertFalse($status->fresh()->is_active);
    }

    public function test_tracking_updates_create_a_timeline_and_keep_member_owner(): void
    {
        $admin = $this->admin();
        $member = User::factory()->create();
        $other = User::factory()->create();
        $start = TrackingStatus::where('is_final', false)->firstOrFail();
        $finish = TrackingStatus::where('label', 'Selesai')->firstOrFail();
        $this->actingAs($admin)->post('/admin/tracking', ['user_id' => $member->id, 'title' => 'Project tracking', 'tracking_status_id' => $start->id, 'note' => 'Dimulai', 'quantity' => 1, 'unit_price' => 100000, 'discount' => 0])->assertSessionHasNoErrors();
        $tracking = MemberTracking::firstOrFail();
        $this->get('/admin/tracking/'.$tracking->id.'/edit')->assertOk();
        $this->put('/admin/tracking/'.$tracking->id, ['user_id' => $other->id, 'title' => 'Project tracking', 'tracking_status_id' => $finish->id, 'note' => 'Selesai dikerjakan'])->assertSessionHasNoErrors();
        $this->assertSame($member->id, $tracking->fresh()->user_id);
        $this->assertNotNull($tracking->fresh()->closed_at);
        $this->assertSame(2, $tracking->events()->count());
        $this->actingAs($member)->get('/tracking/'.$tracking->id)->assertOk()->assertSee('Selesai dikerjakan')->assertSee('Dimulai');
    }

    public function test_bill_total_is_computed_and_payment_credits_points_only_once(): void
    {
        $admin = $this->admin();
        $member = User::factory()->create();
        $this->actingAs($admin)->post('/admin/tracking', ['user_id' => $member->id, 'tracking_status_id' => TrackingStatus::first()->id, 'title' => 'New bill', 'quantity' => 2, 'unit_price' => 200000, 'discount' => 100000, 'total' => 1])->assertSessionHasNoErrors();
        $bill = Bill::firstOrFail();
        $this->assertSame(300000, $bill->total);
        $this->get('/admin/bills/'.$bill->id.'/edit')->assertRedirect('/admin/bills/'.$bill->id);
        $this->post('/admin/bills/'.$bill->id.'/pay', ['payment' => 'QRIS'])->assertSessionHasNoErrors();
        $this->post('/admin/bills/'.$bill->id.'/pay', ['payment' => 'QRIS'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('membership_transactions', 1);
        $this->assertDatabaseCount('point_entries', 1);
        $this->assertDatabaseHas('point_entries', ['user_id' => $member->id, 'points' => 300]);
        $this->assertSame($admin->id, $bill->fresh()->paid_by);
        $this->get('/admin/bills/'.$bill->id)->assertOk()->assertSee('Lunas');
        $this->put('/admin/bills/'.$bill->id, ['title' => 'Changed', 'quantity' => 1, 'unit_price' => 1, 'discount' => 0])->assertForbidden();
        $this->post('/admin/bills/'.$bill->id.'/cancel')->assertSessionHasErrors('bill');
        $this->assertSame(300000, $bill->fresh()->total);
    }

    public function test_bill_cannot_be_attached_to_another_members_tracking(): void
    {
        $admin = $this->admin();
        $member = User::factory()->create();
        $other = User::factory()->create();
        $tracking = $this->tracking($other, $admin);
        $this->actingAs($admin)->post('/admin/bills', ['user_id' => $member->id, 'member_tracking_id' => $tracking->id, 'title' => 'Wrong owner', 'quantity' => 1, 'unit_price' => 100000, 'discount' => 0])->assertForbidden();
        $this->assertDatabaseCount('bills', 0);
    }

    public function test_cancelled_bills_and_invalid_discounts_do_not_generate_points(): void
    {
        $admin = $this->admin();
        $member = User::factory()->create();
        $this->actingAs($admin)->post('/admin/tracking', ['user_id' => $member->id, 'tracking_status_id' => TrackingStatus::first()->id, 'title' => 'Invalid bill', 'quantity' => 1, 'unit_price' => 10000, 'discount' => 10000])->assertSessionHasErrors('discount');
        $bill = $this->bill($member);
        $this->post('/admin/bills/'.$bill->id.'/cancel')->assertSessionHasNoErrors();
        $this->post('/admin/bills/'.$bill->id.'/pay', ['payment' => 'QRIS'])->assertSessionHasErrors('bill');
        $this->assertDatabaseCount('point_entries', 0);
    }

    public function test_settings_apply_to_future_payments_and_preserve_existing_points(): void
    {
        $admin = $this->admin();
        $member = User::factory()->create();
        $first = $this->bill($member);
        app(BillPayment::class)->pay($first, $admin, 'QRIS');
        $this->actingAs($admin)->put('/admin/settings', ['brand_name' => 'Neo Club', 'support_email' => 'support@example.com', 'rupiah_per_point' => 2000])->assertSessionHasNoErrors();
        $second = Bill::create(['user_id' => $member->id, 'reference' => 'BILL-TEST-002', 'title' => 'Second bill', 'quantity' => 1, 'unit_price' => 200000, 'discount' => 0, 'total' => 200000]);
        app(BillPayment::class)->pay($second, $admin, 'QRIS');
        $this->assertDatabaseHas('point_entries', ['transaction_id' => $first->fresh()->transaction_id, 'points' => 300]);
        $this->assertDatabaseHas('point_entries', ['transaction_id' => $second->fresh()->transaction_id, 'points' => 100]);
        $this->actingAs($member)->get('/profile')->assertOk()->assertSee('Neo Club')->assertViewHas('summary', ['earned' => 400, 'used' => 0, 'balance' => 400]);
        $this->assertSame('2000', DB::table('site_settings')->where('key', 'rupiah_per_point')->value('value'));
    }

    public function test_admin_input_ids_must_be_scalar_integers(): void
    {
        $member = User::factory()->create();
        $this->actingAs($this->admin())->postJson('/admin/points', ['user_id' => [$member->id], 'type' => 'earned', 'points' => 10, 'reason' => 'Test'])->assertUnprocessable()->assertJsonValidationErrors('user_id');
        $this->postJson('/admin/tracking', ['user_id' => $member->id, 'title' => 'Invalid status', 'tracking_status_id' => [1]])->assertUnprocessable()->assertJsonValidationErrors('tracking_status_id');
        $this->postJson('/admin/bills', ['user_id' => [$member->id], 'title' => 'Invalid owner', 'quantity' => 1, 'unit_price' => 100000, 'discount' => 0])->assertForbidden();
        $this->assertDatabaseCount('point_entries', 0);
        $this->assertDatabaseCount('bills', 0);
        $this->assertDatabaseCount('member_trackings', 0);
    }

    public function test_dashboard_shows_only_five_latest_orders_and_all_orders_remain_available(): void
    {
        $this->freezeTime();
        $admin = $this->admin();
        $member = User::factory()->create();
        $orders = collect();
        for ($index = 0; $index < 7; $index++) {
            $orders->push($this->tracking($member, $admin));
        }
        $expected = $orders->reverse()->take(5)->pluck('id')->values()->all();
        $this->actingAs($admin)->get('/admin')->assertOk()
            ->assertViewHas('trackings', fn ($rows) => $rows->modelKeys() === $expected)
            ->assertSee('href="'.route('admin.tracking.index').'"', false);
        $this->get('/admin/tracking')->assertOk()->assertViewHas('trackings', fn ($rows) => $rows->total() === 7);
    }

    public function test_dashboard_counts_completed_tracking_and_legacy_payments_correctly(): void
    {
        $admin = $this->admin();
        $member = User::factory()->create();
        app(MembershipLedger::class)->recordPurchase($member, ['reference' => 'LEGACY-PURCHASE', 'title' => 'Existing purchase', 'category' => 'Workshop', 'amount' => 350000, 'payment' => 'QRIS', 'occurred_at' => now()]);
        $tracking = $this->tracking($member, $admin);
        $final = TrackingStatus::where('label', 'Selesai')->firstOrFail();
        app(TrackingWorkflow::class)->update($tracking, ['tracking_status_id' => $final->id], $admin, 'Completed');
        $final->update(['is_final' => false]);
        $this->actingAs($admin)->get('/admin')->assertViewHas('stats', ['members' => 1, 'active_tracking' => 0, 'unpaid_bills' => 0, 'revenue' => 350000]);
    }
}
