<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MembershipLedger;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MembershipDatabaseTest extends TestCase
{
    use RefreshDatabase;

    private function purchase(string $reference = 'INV-TEST-001', int $amount = 350000): array
    {
        return ['reference' => $reference, 'title' => 'Workshop database', 'category' => 'Workshop', 'amount' => $amount, 'payment' => 'QRIS', 'occurred_at' => '2026-10-14 12:00:00'];
    }

    private function redemption(int $points = 100): array
    {
        return ['reference' => 'RDM-TEST-001', 'title' => 'Voucher member', 'category' => 'Voucher', 'points' => $points, 'description' => 'Voucher pengujian', 'occurred_at' => '2026-10-14 13:00:00'];
    }

    public function test_authentication_forms_render_for_guests(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk ke akun');
        $this->get('/register')->assertNotFound();
    }

    public function test_admin_creates_a_login_account_with_zero_points(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true, 'is_superadmin' => true])->save();
        $this->actingAs($admin)->post('/admin/members', ['name' => 'Member Baru', 'email' => 'Member@example.com', 'password' => 'secret-password', 'password_confirmation' => 'secret-password', 'member_tier' => 'Member', 'is_active' => 1])
            ->assertSessionHasNoErrors()->assertRedirect('/admin/members');
        $user = User::where('email', 'member@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($admin);
        $this->assertSame('member@example.com', $user->email);
        $this->assertNotEmpty($user->member_number);
        $temporaryPassword = Crypt::decryptString(session('temporary_credentials.password'));
        $this->assertTrue(Hash::check($temporaryPassword, $user->password));
        $this->assertNull($user->password_setup_at);
        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => $temporaryPassword])->assertRedirect('/account/setup');
        $this->put('/account/password', ['password' => 'own-password', 'password_confirmation' => 'own-password'])->assertRedirect('/profile');
        $this->get('/profile')->assertOk()->assertViewHas('summary', ['earned' => 0, 'used' => 0, 'balance' => 0])->assertViewHas('transactions', [])->assertViewHas('earnings', [])->assertDontSee('Aditya Pratama');
    }

    public function test_profile_reads_its_own_database_records_and_separate_point_ledger(): void
    {
        $member = User::factory()->create();
        $other = User::factory()->create(['name' => 'Other Private Member']);
        $ledger = app(MembershipLedger::class);
        $ledger->recordPurchase($member, $this->purchase());
        $ledger->redeem($member, $this->redemption());
        $otherPurchase = $this->purchase('INV-PRIVATE-002');
        $otherPurchase['title'] = 'Private activity for another member';
        $ledger->recordPurchase($other, $otherPurchase);
        $member->pointEntries()->create(['reference' => 'BONUS-001', 'type' => 'earned', 'points' => 50, 'title' => 'Bonus loyalitas', 'category' => 'Bonus', 'occurred_at' => '2026-09-10 12:00:00']);

        $this->actingAs($member)->get('/profile')->assertOk()
            ->assertViewHas('summary', ['earned' => 400, 'used' => 100, 'balance' => 300])
            ->assertViewHas('transactions', fn (array $rows): bool => count($rows) === 1 && $rows[0]['id'] === 'INV-TEST-001')
            ->assertViewHas('earnings', fn (array $rows): bool => count($rows) === 2)
            ->assertViewHas('redemptions', fn (array $rows): bool => count($rows) === 1)
            ->assertViewHas('periods', fn (array $periods): bool => array_keys($periods) === ['2026-10', '2026-09'])
            ->assertSee('Bonus loyalitas')->assertDontSee('Other Private Member')->assertDontSee('INV-PRIVATE-002')->assertDontSee('Private activity for another member');
    }

    public function test_purchase_below_one_point_has_no_phantom_earning(): void
    {
        $user = User::factory()->create();
        app(MembershipLedger::class)->recordPurchase($user, $this->purchase(amount: 999));
        $this->assertDatabaseCount('membership_transactions', 1);
        $this->assertDatabaseCount('point_entries', 0);
        $this->actingAs($user)->get('/profile')->assertOk()->assertViewHas('earnings', [])->assertViewHas('summary', ['earned' => 0, 'used' => 0, 'balance' => 0]);
    }

    public function test_insufficient_points_do_not_create_a_redemption(): void
    {
        $user = User::factory()->create();
        try {
            app(MembershipLedger::class)->redeem($user, $this->redemption(1));
            $this->fail('A member must not spend more than their balance.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('points', $exception->errors());
        }
        $this->assertDatabaseCount('point_entries', 0);
    }

    public function test_duplicate_purchase_does_not_award_points_twice(): void
    {
        $user = User::factory()->create();
        $ledger = app(MembershipLedger::class);
        $ledger->recordPurchase($user, $this->purchase());
        try {
            $ledger->recordPurchase($user, $this->purchase());
            $this->fail('Duplicate payment reference must be rejected.');
        } catch (QueryException) {
            $this->assertDatabaseCount('membership_transactions', 1);
            $this->assertDatabaseCount('point_entries', 1);
        }
    }

    public function test_database_login_logout_and_wrong_password(): void
    {
        $user = User::factory()->create(['email' => 'member@example.com', 'password' => 'secret-password']);
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/login', ['email' => $user->email, 'password' => 'secret-password'])->assertSessionHasNoErrors()->assertRedirect('/profile');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_duplicate_email_and_password_confirmation_are_validated(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true, 'is_superadmin' => true])->save();
        $this->actingAs($admin)->post('/admin/members', ['name' => 'Member', 'email' => 'taken@example.com', 'password' => 'secret-password', 'password_confirmation' => 'different-password', 'member_tier' => 'Member', 'is_active' => 1])->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 2);
        $this->assertAuthenticatedAs($admin);
        $member = User::where('email', 'taken@example.com')->firstOrFail();
        $password = $member->password;
        $this->actingAs($member)->put('/account/password', ['current_password' => 'password', 'password' => 'secret-password', 'password_confirmation' => 'different-password'])->assertSessionHasErrors('password');
        $this->assertSame($password, $member->fresh()->password);
    }
}
