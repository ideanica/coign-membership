<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\MemberTracking;
use App\Models\TrackingStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffAccountWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_have_no_member_pages_or_navigation(): void
    {
        foreach ([false, true] as $superadmin) {
            $staff = User::factory()->create(['is_admin' => true, 'is_superadmin' => $superadmin]);
            $tracking = MemberTracking::create(['user_id' => $staff->id, 'tracking_status_id' => TrackingStatus::firstOrFail()->id, 'reference' => 'ORDER-'.(int) $superadmin, 'title' => 'Order']);
            $bill = Bill::create(['user_id' => $staff->id, 'reference' => 'BILL-'.(int) $superadmin, 'title' => 'Bill', 'quantity' => 1, 'unit_price' => 1000, 'discount' => 0, 'total' => 1000]);
            $this->actingAs($staff);
            foreach (['/profile', '/tracking', '/tracking/'.$tracking->id, '/bills', '/bills/'.$bill->id] as $path) {
                $this->get($path)->assertRedirect('/admin');
            }
            foreach (['/account/security', '/account/setup'] as $path) {
                $this->get($path)->assertRedirect('/admin/settings');
            }
            $this->get('/catalog')->assertOk()->assertDontSee('href="'.route('membership.profile').'"', false)
                ->assertDontSee('href="'.route('membership.tracking.index').'"', false);
            $this->get('/admin/settings')->assertOk()->assertSee('settings-profile-dialog')
                ->assertSee('settings-password-dialog')->assertDontSee('Halaman member');
        }
    }

    public function test_admin_cannot_view_or_submit_brand_controls(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get('/admin/settings')->assertOk()->assertDontSee('settings-identity-dialog')->assertDontSee('settings-contact-dialog');
        foreach ([['_settings_section' => 'identity', 'brand_name' => 'Forged'], ['_settings_section' => 'contact', 'support_email' => 'forged@example.com'], ['brand_name' => 'Forged']] as $payload) {
            $this->put('/admin/settings', $payload)->assertForbidden();
        }
    }

    public function test_staff_can_update_own_profile_without_changing_role_or_other_accounts(): void
    {
        foreach ([false, true] as $superadmin) {
            $staff = User::factory()->create(['is_admin' => true, 'is_superadmin' => $superadmin, 'google_id' => 'google-'.(int) $superadmin]);
            $other = User::factory()->create();
            $this->actingAs($staff)->put('/admin/settings/profile', ['name' => 'Updated', 'email' => 'updated'.(int) $superadmin.'@example.com', 'id' => $other->id, 'is_admin' => false, 'is_superadmin' => ! $superadmin])
                ->assertSessionHasNoErrors()->assertRedirect('/admin/settings');
            $this->assertSame('Updated', $staff->fresh()->name);
            $this->assertTrue($staff->fresh()->is_admin);
            $this->assertSame($superadmin, $staff->fresh()->is_superadmin);
            $this->assertNull($staff->fresh()->google_id);
            $this->assertSame($other->email, $other->fresh()->email);
            $this->put('/admin/settings/profile', ['name' => 'Updated', 'email' => $other->email])->assertSessionHasErrors('email');
        }
    }

    public function test_staff_password_requires_current_password_even_without_setup_date(): void
    {
        $staff = User::factory()->create(['is_admin' => true, 'password_setup_at' => null]);
        $this->actingAs($staff);
        $this->put('/admin/settings/password', ['password' => 'new-password', 'password_confirmation' => 'new-password'])->assertSessionHasErrors('current_password');
        $this->put('/admin/settings/password', ['current_password' => 'wrong', 'password' => 'new-password', 'password_confirmation' => 'new-password'])->assertSessionHasErrors('current_password');
        $this->put('/admin/settings/password', ['current_password' => 'password', 'password' => 'new-password', 'password_confirmation' => 'new-password'])->assertRedirect('/admin/settings');
        $this->assertTrue(Hash::check('new-password', $staff->fresh()->password));
    }

    public function test_member_cannot_update_staff_settings_and_retains_member_pages(): void
    {
        $member = User::factory()->create(['password_setup_at' => now()]);
        $this->actingAs($member)->get('/profile')->assertOk();
        $this->get('/tracking')->assertOk();
        $this->put('/admin/settings/profile', ['name' => 'Forged', 'email' => $member->email])->assertForbidden();
        $this->put('/admin/settings/password', [])->assertForbidden();
    }
}
