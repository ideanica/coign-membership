<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\MembershipTransaction;
use App\Models\PointEntry;
use App\Models\TrackingStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DownPaymentBillTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return [...[
            'title' => 'Paket undangan',
            'tracking_status_id' => TrackingStatus::first()->id,
            'customer_name' => 'Pelanggan DP',
            'items' => [
                ['name' => 'Desain undangan', 'quantity' => 1, 'unit_price' => 500000],
            ],
            'discount' => 0,
            'payment_type' => 'dp',
            'dp_amount' => 200000,
        ], ...$overrides];
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_order_form_offers_deposit_and_full_payment_modes(): void
    {
        $this->actingAs($this->admin())->get('/admin/tracking/create')->assertOk()
            ->assertSee('name="payment_type"', false)
            ->assertSee('name="dp_amount"', false)
            ->assertSee('Lunas (tanpa DP)')
            ->assertSee('DP (bayar sebagian)');
    }

    public function test_dp_order_records_deposit_and_remaining_balance_without_points(): void
    {
        $this->actingAs($this->admin())->post('/admin/tracking', $this->payload())->assertSessionHasNoErrors();

        $bill = Bill::sole();
        $this->assertSame('dp', $bill->payment_type);
        $this->assertSame(200000, $bill->dp_amount);
        $this->assertSame(500000, $bill->total);
        $this->assertSame(300000, $bill->remainingAmount());
        $this->assertTrue($bill->isPartiallyPaid());
        $this->assertSame('unpaid', $bill->status);
        $this->assertNotNull($bill->dp_paid_at);
        $this->assertDatabaseCount('membership_transactions', 0);
        $this->assertDatabaseCount('point_entries', 0);
        $this->get('/admin/bills/'.$bill->id)->assertOk()->assertSee('DP dibayar')->assertSee('Rp200.000')->assertSee('Rp300.000');
    }

    public function test_unsettled_deposit_counts_as_received_payment_on_dashboard(): void
    {
        $this->actingAs($this->admin())->post('/admin/tracking', $this->payload())->assertSessionHasNoErrors();

        $this->get('/admin')->assertOk()->assertViewHas('stats', fn (array $stats): bool => $stats['revenue'] === 200000);
    }

    public function test_full_payment_order_stores_no_deposit(): void
    {
        $this->actingAs($this->admin())->post('/admin/tracking', $this->payload(['payment_type' => 'full', 'dp_amount' => 200000]))->assertSessionHasNoErrors();

        $bill = Bill::sole();
        $this->assertSame('full', $bill->payment_type);
        $this->assertSame(0, $bill->dp_amount);
        $this->assertSame(500000, $bill->remainingAmount());
        $this->assertFalse($bill->isPartiallyPaid());
    }

    public function test_deposit_must_be_positive_and_lower_than_the_bill_total(): void
    {
        $this->actingAs($this->admin());

        $this->post('/admin/tracking', $this->payload(['dp_amount' => 500000]))
            ->assertSessionHasErrors(['dp_amount' => 'Jumlah DP harus lebih kecil dari total tagihan. Pilih Lunas jika pelanggan membayar penuh.']);
        $this->post('/admin/tracking', $this->payload(['dp_amount' => 0]))->assertSessionHasErrors('dp_amount');
        $this->post('/admin/tracking', $this->payload(['dp_amount' => null]))->assertSessionHasErrors('dp_amount');

        $this->assertDatabaseCount('member_trackings', 0);
        $this->assertDatabaseCount('bills', 0);
    }

    public function test_settling_a_dp_bill_records_the_full_purchase_and_points_once(): void
    {
        $admin = $this->admin();
        $member = User::factory()->create();
        DB::table('site_settings')->updateOrInsert(['key' => 'points_mode'], ['value' => 'ratio']);
        DB::table('site_settings')->updateOrInsert(['key' => 'rupiah_per_point'], ['value' => '1000']);
        $this->actingAs($admin)->post('/admin/tracking', $this->payload(['user_id' => $member->id]))->assertSessionHasNoErrors();
        $bill = Bill::sole();

        $this->post('/admin/bills/'.$bill->id.'/pay', ['payment' => 'Transfer bank'])->assertSessionHasNoErrors();
        $this->post('/admin/bills/'.$bill->id.'/pay', ['payment' => 'Transfer bank'])->assertSessionHasNoErrors();

        $this->assertSame('paid', $bill->fresh()->status);
        $this->assertSame(0, $bill->fresh()->remainingAmount());
        $this->assertSame(200000, $bill->fresh()->dp_amount);
        $this->assertDatabaseCount('membership_transactions', 1);
        $this->assertSame(500000, MembershipTransaction::sole()->amount);
        $this->assertDatabaseCount('point_entries', 1);
        $this->assertSame(500, PointEntry::sole()->points);
    }
}
