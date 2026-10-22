<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\MembershipTransaction;
use App\Models\PointEntry;
use App\Models\TrackingStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MultiServiceOrderTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return [...[
            'title' => 'Paket acara',
            'tracking_status_id' => TrackingStatus::first()->id,
            'customer_name' => 'Pelanggan tamu',
            'items' => [
                ['name' => 'Desain undangan', 'quantity' => 2, 'unit_price' => 150000],
                ['name' => 'Video acara', 'quantity' => 1, 'unit_price' => 200000],
            ],
            'discount' => 50000,
        ], ...$overrides];
    }

    public function test_multiple_services_are_totalled_by_server_and_visible_only_to_owner(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create();
        $payload = $this->payload(['user_id' => $member->id, 'quantity' => 999, 'unit_price' => 1, 'total' => 1]);

        $this->actingAs($admin)->post('/admin/tracking', $payload)->assertSessionHasNoErrors();

        $bill = Bill::sole();
        $this->assertSame(500000, $bill->subtotal());
        $this->assertSame(450000, $bill->total);
        $this->assertSame($payload['items'], $bill->lineItems());
        $this->assertDatabaseCount('member_trackings', 1);
        $this->assertDatabaseCount('bills', 1);
        $this->assertDatabaseCount('point_entries', 0);
        $this->get('/admin/tracking/'.$bill->member_tracking_id)->assertOk()->assertSee('Desain undangan')->assertSee('Video acara')->assertSee('Rp450.000');
        $this->get('/admin/bills/'.$bill->id)->assertOk()->assertSee('Rp300.000')->assertSee('Rp200.000')->assertSee('Rp500.000')->assertSee('Rp450.000');
        $this->actingAs($member)->get('/tracking/'.$bill->member_tracking_id)->assertOk()->assertSee('Desain undangan')->assertSee('Video acara')->assertSee('Rp450.000');
        $this->actingAs(User::factory()->create())->get('/tracking/'.$bill->member_tracking_id)->assertNotFound();
    }

    public function test_payment_awards_points_once_for_final_bill_total(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create();
        DB::table('site_settings')->updateOrInsert(['key' => 'points_mode'], ['value' => 'ratio']);
        DB::table('site_settings')->updateOrInsert(['key' => 'rupiah_per_point'], ['value' => '1000']);
        $this->actingAs($admin)->post('/admin/tracking', $this->payload(['user_id' => $member->id]))->assertSessionHasNoErrors();
        $bill = Bill::sole();

        $this->post('/admin/bills/'.$bill->id.'/pay', ['payment' => 'Transfer bank'])->assertSessionHasNoErrors();
        $this->post('/admin/bills/'.$bill->id.'/pay', ['payment' => 'Transfer bank'])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('membership_transactions', 1);
        $this->assertDatabaseCount('point_entries', 1);
        $this->assertSame(450000, MembershipTransaction::sole()->amount);
        $this->assertSame(450, PointEntry::sole()->points);
        $this->assertSame('paid', $bill->fresh()->status);
    }

    public function test_guest_multiple_services_can_be_paid_without_member_points(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))->post('/admin/tracking', $this->payload())->assertSessionHasNoErrors();
        $bill = Bill::sole();

        $this->post('/admin/bills/'.$bill->id.'/pay', ['payment' => 'QRIS'])->assertSessionHasNoErrors();

        $this->assertNull($bill->user_id);
        $this->assertSame('paid', $bill->fresh()->status);
        $this->assertDatabaseCount('membership_transactions', 0);
        $this->assertDatabaseCount('point_entries', 0);
    }

    public function test_empty_malformed_and_invalid_item_values_do_not_create_an_order(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        foreach ([[], 'invalid', [['name' => '', 'quantity' => 0, 'unit_price' => -1]], [['name' => ['invalid'], 'quantity' => 1, 'unit_price' => 10]], [['name' => 'Jasa', 'quantity' => 1.5, 'unit_price' => 10]], [['name' => 'Jasa', 'quantity' => 1, 'unit_price' => 10, 'subtotal' => 1]]] as $items) {
            $response = $this->post('/admin/tracking', $this->payload(['items' => $items, 'quantity' => 1, 'unit_price' => 100000]));
            $response->assertSessionHasErrors();
        }
        $this->assertDatabaseCount('member_trackings', 0);
        $this->assertDatabaseCount('bills', 0);
    }

    public function test_discount_and_order_size_limits_use_all_items(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->post('/admin/tracking', $this->payload(['discount' => 500000]))->assertSessionHasErrors('discount');
        $this->post('/admin/tracking', $this->payload(['items' => array_fill(0, 101, ['name' => 'Jasa', 'quantity' => 1, 'unit_price' => 1000])]))->assertSessionHasErrors('items');
        $this->post('/admin/tracking', $this->payload(['items' => array_fill(0, 2, ['name' => 'Jasa', 'quantity' => 10000, 'unit_price' => 1000000000])]))->assertSessionHasErrors('items');
        $this->assertDatabaseCount('member_trackings', 0);
    }

    public function test_old_bill_without_items_keeps_original_price_and_printable_details(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))->post('/admin/tracking', $this->payload());
        $bill = Bill::sole();
        DB::table('bills')->where('id', $bill->id)->update(['items' => null, 'quantity' => 2, 'unit_price' => 250000, 'service_name' => 'Layanan lama']);
        $bill->refresh();

        $this->assertSame([['name' => 'Layanan lama', 'quantity' => 2, 'unit_price' => 250000]], $bill->lineItems());
        $this->assertSame(500000, $bill->subtotal());
        $this->get('/admin/bills/'.$bill->id)->assertOk()->assertSee('Layanan lama')->assertSee('Rp500.000')->assertSee('Rp450.000');
    }

    public function test_items_remain_locked_when_tracking_is_updated(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))->post('/admin/tracking', $this->payload());
        $bill = Bill::sole();
        $original = $bill->items;
        $this->put('/admin/tracking/'.$bill->member_tracking_id, $this->payload(['title' => 'Judul baru', 'items' => [['name' => 'Pengganti', 'quantity' => 1, 'unit_price' => 1]]]))->assertSessionHasNoErrors();
        $this->assertSame($original, $bill->fresh()->items);
        $this->assertSame(450000, $bill->fresh()->total);

        $this->expectException(ValidationException::class);
        $bill->update(['items' => [['name' => 'Pengganti', 'quantity' => 1, 'unit_price' => 1]]]);
    }

    public function test_member_cannot_create_multiple_service_orders(): void
    {
        $this->actingAs(User::factory()->create())->post('/admin/tracking', $this->payload())->assertForbidden();
        $this->assertDatabaseCount('member_trackings', 0);
    }
}
