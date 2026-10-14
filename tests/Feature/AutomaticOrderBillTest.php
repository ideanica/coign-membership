<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\MemberTracking;
use App\Models\Service;
use App\Models\TrackingStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutomaticOrderBillTest extends TestCase
{
    use RefreshDatabase;

    private function order(array $overrides = []): array
    {
        return [...['title' => 'Website pesanan', 'tracking_status_id' => TrackingStatus::first()->id, 'customer_name' => 'Guest customer', 'customer_email' => 'guest@example.com', 'quantity' => 2, 'unit_price' => 200000, 'discount' => 100000, 'total' => 1], ...$overrides];
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_order_issues_one_locked_bill_and_payment_is_idempotent(): void
    {
        $admin = $this->admin();
        $member = User::factory()->create(['name' => 'Original customer']);
        $service = Service::create(['name' => 'Original service', 'code' => 'WEB', 'price' => 200000, 'delivery_days' => 3, 'is_active' => true]);
        $this->actingAs($admin)->post('/admin/tracking', $this->order(['user_id' => $member->id, 'service_id' => $service->id]))->assertSessionHasNoErrors();
        $bill = Bill::firstOrFail();
        $this->assertSame(300000, $bill->total);
        $this->assertSame(MemberTracking::first()->id, $bill->member_tracking_id);
        $this->assertSame($admin->id, $bill->issued_by);
        $member->update(['name' => 'Changed customer']);
        $service->update(['name' => 'Changed service', 'price' => 1]);
        $this->get('/admin/bills/'.$bill->id)->assertOk()->assertSee('Original customer')->assertSee('Original service')->assertDontSee('Edit bill');
        $this->put('/admin/bills/'.$bill->id, ['total' => 1])->assertForbidden();
        $this->put('/admin/tracking/'.$bill->member_tracking_id, $this->order(['service_id' => null]))->assertSessionHasErrors('service_id');
        $this->post('/admin/bills', $this->order())->assertForbidden();
        $this->post('/admin/bills/'.$bill->id.'/pay', ['payment' => 'QRIS'])->assertSessionHasNoErrors();
        $this->post('/admin/bills/'.$bill->id.'/pay', ['payment' => 'QRIS'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('bills', 1);
        $this->assertDatabaseCount('membership_transactions', 1);
        $this->assertDatabaseCount('point_entries', 1);
    }

    public function test_guest_bill_can_be_paid_without_creating_member_points_and_is_private(): void
    {
        $this->actingAs($this->admin())->post('/admin/tracking', $this->order())->assertSessionHasNoErrors();
        $bill = Bill::firstOrFail();
        $this->assertNull($bill->user_id);
        $this->get('/admin/bills/'.$bill->id)->assertOk()->assertSee('Guest customer');
        $this->post('/admin/bills/'.$bill->id.'/pay', ['payment' => 'Transfer bank'])->assertSessionHasNoErrors();
        $this->post('/admin/bills/'.$bill->id.'/pay', ['payment' => 'Transfer bank'])->assertSessionHasNoErrors();
        $this->assertSame('paid', $bill->fresh()->status);
        $this->assertDatabaseCount('point_entries', 0);
        $this->actingAs(User::factory()->create())->get('/bills/'.$bill->id)->assertNotFound();
    }

    public function test_invalid_quote_creates_neither_order_nor_bill(): void
    {
        $this->actingAs($this->admin())->post('/admin/tracking', $this->order(['discount' => 400000]))->assertSessionHasErrors('discount');
        $this->post('/admin/tracking', $this->order(['customer_name' => null]))->assertSessionHasErrors('customer_name');
        $this->assertDatabaseCount('member_trackings', 0);
        $this->assertDatabaseCount('bills', 0);
    }

    public function test_bill_failure_rolls_back_order_and_timeline(): void
    {
        $this->withoutExceptionHandling();
        Bill::creating(fn () => throw new \RuntimeException('Simulated bill failure'));
        try {
            $this->actingAs($this->admin())->post('/admin/tracking', $this->order());
            $this->fail('Expected bill creation to fail.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Simulated bill failure', $error->getMessage());
        }
        $this->assertDatabaseCount('member_trackings', 0);
        $this->assertDatabaseCount('tracking_events', 0);
        $this->assertDatabaseCount('bills', 0);
    }
}
