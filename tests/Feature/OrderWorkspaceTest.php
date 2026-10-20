<?php

namespace Tests\Feature;

use App\Models\MemberTracking;
use App\Models\TrackingStatus;
use App\Models\User;
use App\Services\TrackingWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private function order(User $owner, User $admin): MemberTracking
    {
        return app(TrackingWorkflow::class)->create([
            'user_id' => $owner->id,
            'title' => 'Website pelanggan privat',
            'tracking_status_id' => TrackingStatus::first()->id,
        ], $admin, 'Catatan khusus pelanggan', ['quantity' => 2, 'unit_price' => 200000, 'discount' => 50000]);
    }

    public function test_manual_order_creates_a_private_customer_order_and_embedded_bill(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/tracking', [
            'user_id' => $owner->id,
            'title' => 'Website dari form manual',
            'description' => 'Rincian pekerjaan pesanan',
            'tracking_status_id' => TrackingStatus::first()->id,
            'quantity' => 2, 'unit_price' => 200000, 'discount' => 50000,
        ]);

        $order = MemberTracking::sole();
        $response->assertSessionHasNoErrors()->assertRedirect(route('admin.tracking.show', $order));
        $this->assertDatabaseHas('member_trackings', ['id' => $order->id, 'user_id' => $owner->id, 'service_id' => null, 'public_enabled' => false]);
        $this->assertDatabaseHas('bills', ['member_tracking_id' => $order->id, 'user_id' => $owner->id, 'total' => 350000]);
        $this->assertDatabaseCount('point_entries', 0);
        $this->get(route('admin.tracking.show', $order))->assertOk()->assertSee($order->bill->reference)->assertSee('Tandai lunas');
    }

    public function test_only_the_owner_sees_the_order_and_its_embedded_bill(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = $this->order($owner, $admin);

        $this->actingAs($owner)->get('/tracking/'.$order->id)->assertOk()
            ->assertSee($order->bill->reference)->assertSee('Rp350.000')->assertDontSee('Tandai lunas');
        $this->actingAs($other)->get('/tracking/'.$order->id)->assertNotFound();
        $this->get('/tracking')->assertOk()->assertDontSee('Website pelanggan privat');
        $this->get('/bills/'.$order->bill->id)->assertNotFound();
    }

    public function test_navigation_uses_orders_and_creation_does_not_require_a_service_catalog(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/admin/tracking/create')->assertOk()
            ->assertSee('Pesanan')->assertSee('name="unit_price"', false)
            ->assertDontSee('name="service_id"', false)
            ->assertDontSee('href="'.route('admin.services.index').'"', false)
            ->assertDontSee('href="'.route('admin.bills.index').'"', false);
        $this->actingAs(User::factory()->create())->get('/tracking')->assertOk()
            ->assertDontSee('href="'.route('membership.bills.index').'"', false);
    }

    public function test_payment_and_cancellation_return_to_the_related_order(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create();
        $paidOrder = $this->order($owner, $admin);
        $cancelledOrder = $this->order($owner, $admin);

        $this->actingAs($admin)->post('/admin/bills/'.$paidOrder->bill->id.'/pay', ['payment' => 'QRIS'])
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.tracking.show', $paidOrder));
        $this->assertDatabaseHas('bills', ['id' => $paidOrder->bill->id, 'status' => 'paid']);
        $this->assertDatabaseCount('membership_transactions', 1);
        $this->post('/admin/bills/'.$cancelledOrder->bill->id.'/cancel')
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.tracking.show', $cancelledOrder));
        $this->assertDatabaseHas('bills', ['id' => $cancelledOrder->bill->id, 'status' => 'cancelled']);
    }

    public function test_admin_order_detail_requires_admin_access(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create();
        $order = $this->order($owner, $admin);

        $this->get('/admin/tracking/'.$order->id)->assertRedirect(route('login'));
        $this->actingAs($owner)->get('/admin/tracking/'.$order->id)->assertForbidden();
    }

    public function test_payment_filter_lists_only_orders_with_matching_bills(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create();
        $unpaid = $this->order($owner, $admin);
        $cancelled = $this->order($owner, $admin);
        $cancelled->bill->forceFill(['status' => 'cancelled'])->save();

        $this->actingAs($admin)->get('/admin/tracking?payment_status=unpaid')->assertOk()
            ->assertViewHas('trackings', fn ($rows): bool => $rows->modelKeys() === [$unpaid->id]);
    }

    public function test_tracking_can_be_managed_directly_from_order_details(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $owner = User::factory()->create();
        $order = $this->order($owner, $admin);
        $status = TrackingStatus::where('is_final', true)->firstOrFail();

        $this->actingAs($admin)->get(route('admin.tracking.show', $order))->assertOk()
            ->assertSee('Kelola tracking pesanan')
            ->assertSee('action="'.route('admin.tracking.update', $order).'"', false)
            ->assertSee('name="tracking_status_id"', false)
            ->assertSee('name="note"', false)
            ->assertDontSee('href="'.route('admin.tracking.edit', $order).'"', false);
        $this->put(route('admin.tracking.update', $order), [
            'title' => $order->title,
            'tracking_status_id' => $status->id,
            'note' => 'Pesanan selesai dari halaman detail',
            'public_enabled' => false,
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.tracking.show', $order));

        $this->assertDatabaseHas('member_trackings', ['id' => $order->id, 'user_id' => $owner->id, 'tracking_status_id' => $status->id]);
        $this->assertDatabaseHas('tracking_events', ['member_tracking_id' => $order->id, 'note' => 'Pesanan selesai dari halaman detail']);
        $this->assertDatabaseCount('bills', 1);
    }

    public function test_order_actions_are_in_dialogs_and_invoice_has_no_duplicate_controls(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $order = $this->order(User::factory()->create(), $admin);
        $response = $this->actingAs($admin)->get(route('admin.tracking.show', $order))->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);

        $this->assertSame(1, $xpath->query('//dialog[@id="order-edit-dialog"]//form')->length);
        $this->assertSame(1, $xpath->query('//dialog[@id="order-payment-dialog"]//select[@name="payment"]')->length);
        $this->assertSame(0, $xpath->query('//*[@id="bill"]//form')->length);
        $this->assertSame(1, $xpath->query('//*[@id="bill" and contains(@class,"order-print-only")]//article')->length);
        $this->assertSame(1, $xpath->query('//button[@data-print]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="tracking_status_id"]')->length);
    }

    public function test_invalid_tracking_update_reopens_the_edit_dialog_and_preserves_input(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $order = $this->order(User::factory()->create(), $admin);

        $response = $this->actingAs($admin)->followingRedirects()->from(route('admin.tracking.show', $order))
            ->put(route('admin.tracking.update', $order), [
                '_method' => 'PUT', 'title' => '', 'tracking_status_id' => $order->tracking_status_id,
                'note' => 'Catatan yang belum tersimpan',
            ])->assertOk()->assertSee('Catatan yang belum tersimpan');
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);

        $this->assertSame(1, $xpath->query('//dialog[@id="order-edit-dialog" and @data-open-on-error]')->length);
        $this->assertDatabaseCount('tracking_events', 1);
    }
}
