<?php

namespace Tests\Feature;

use App\Models\PointRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function staff(bool $superadmin = false): User
    {
        return User::factory()->create(['is_admin' => true, 'is_superadmin' => $superadmin]);
    }

    public function test_admin_cannot_read_or_write_any_member_and_point_menu_endpoint(): void
    {
        $admin = $this->staff();
        $member = User::factory()->create();
        $rule = PointRule::create(['name' => 'Protected rule', 'minimum_amount' => 100000, 'points' => 10, 'is_active' => true]);
        $this->actingAs($admin);

        foreach ([
            ['GET', '/admin/members'], ['GET', '/admin/members/create'], ['POST', '/admin/members'],
            ['GET', '/admin/members/'.$member->id.'/edit'], ['PUT', '/admin/members/'.$member->id],
            ['PATCH', '/admin/members/'.$member->id], ['DELETE', '/admin/members/'.$member->id],
            ['GET', '/admin/points'], ['POST', '/admin/points'],
            ['GET', '/admin/point-rules'], ['POST', '/admin/point-rules'],
            ['POST', '/admin/point-rules/mode'], ['POST', '/admin/point-rules/purchase'],
            ['PUT', '/admin/point-rules/'.$rule->id], ['PATCH', '/admin/point-rules/'.$rule->id],
            ['DELETE', '/admin/point-rules/'.$rule->id],
        ] as [$method, $path]) {
            $this->call($method, $path, ['is_admin' => true, 'is_superadmin' => true])->assertForbidden();
        }

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('point_entries', 0);
        $this->assertDatabaseHas('users', ['id' => $member->id, 'is_active' => true, 'is_admin' => false]);
        $this->assertModelExists($rule);
    }

    public function test_admin_retains_other_operational_pages_without_member_and_point_navigation(): void
    {
        $this->actingAs($this->staff());

        foreach (['/admin', '/admin/catalogs', '/admin/discounts', '/admin/catalog-categories', '/admin/testimonials', '/admin/tracking', '/admin/tracking/create', '/admin/statuses', '/admin/bills', '/admin/services', '/admin/settings'] as $path) {
            $this->get($path)->assertOk()
                ->assertDontSee('Member &amp; poin', false)
                ->assertDontSee('href="'.route('admin.members.index').'"', false)
                ->assertDontSee('href="'.route('admin.points.index').'"', false)
                ->assertDontSee('href="'.route('admin.point-rules.index').'"', false);
        }
    }

    public function test_superadmin_has_full_menu_access_and_can_create_a_member(): void
    {
        $this->actingAs($this->staff(true));

        foreach (['/admin', '/admin/members', '/admin/members/create', '/admin/points', '/admin/point-rules', '/admin/settings'] as $path) {
            $this->get($path)->assertOk()->assertSee('Superadmin')->assertSee(route('admin.members.index'));
        }
        $this->post('/admin/members', ['name' => 'New customer', 'email' => 'customer@example.com', 'member_tier' => 'Member', 'is_active' => 1])
            ->assertSessionHasNoErrors()->assertRedirect('/admin/members');
        $this->assertDatabaseHas('users', ['email' => 'customer@example.com', 'is_admin' => false, 'is_superadmin' => false]);
    }

    public function test_admin_cannot_change_brand_contact_or_point_settings(): void
    {
        $this->actingAs($this->staff());
        $settings = ['brand_name' => 'Coignitive', 'support_email' => 'studio@example.com'];
        $originalRatio = DB::table('site_settings')->where('key', 'rupiah_per_point')->value('value');

        $this->put('/admin/settings', $settings)->assertForbidden();
        $this->assertDatabaseMissing('site_settings', ['key' => 'support_email', 'value' => 'studio@example.com']);
        $this->put('/admin/settings', [...$settings, 'rupiah_per_point' => 1])->assertForbidden();
        $this->assertSame($originalRatio, DB::table('site_settings')->where('key', 'rupiah_per_point')->value('value'));
        $this->get('/admin/settings')->assertOk()->assertDontSee('name="rupiah_per_point"', false);
    }

    public function test_member_cannot_claim_superadmin_access_with_a_flag_alone(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false, 'is_superadmin' => true]));

        $this->get('/admin/members')->assertForbidden();
        $this->get('/admin')->assertForbidden();
    }

    public function test_inactive_superadmin_cannot_keep_access(): void
    {
        $superadmin = $this->staff(true);
        $superadmin->forceFill(['is_active' => false])->save();

        $this->actingAs($superadmin)->get('/admin/members')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_command_can_change_an_admin_role_but_preserves_the_last_superadmin(): void
    {
        $superadmin = $this->staff(true);
        $admin = $this->staff();
        $password = $admin->password;

        $this->artisan('admin:role', ['email' => $superadmin->email, 'role' => 'admin'])->assertFailed();
        $this->assertTrue($superadmin->fresh()->isSuperAdmin());
        $this->artisan('admin:role', ['email' => $admin->email, 'role' => 'superadmin'])->assertSuccessful();
        $this->assertTrue($admin->fresh()->isSuperAdmin());
        $this->artisan('admin:role', ['email' => $superadmin->email, 'role' => 'admin'])->assertSuccessful();
        $this->assertFalse($superadmin->fresh()->isSuperAdmin());
        $this->assertSame($password, $admin->fresh()->password);
    }

    public function test_create_admin_command_supports_both_roles(): void
    {
        foreach (['admin', 'superadmin'] as $role) {
            $this->artisan('admin:create', ['email' => $role.'@example.com', '--role' => $role, '--generate-password' => true])->assertSuccessful();
            $this->assertDatabaseHas('users', ['email' => $role.'@example.com', 'is_admin' => true, 'is_superadmin' => $role === 'superadmin']);
        }
    }

    public function test_role_command_rejects_invalid_roles_and_member_accounts(): void
    {
        $member = User::factory()->create();
        $admin = $this->staff();

        $this->artisan('admin:role', ['email' => $member->email, 'role' => 'superadmin'])->assertFailed();
        $this->artisan('admin:role', ['email' => $admin->email, 'role' => 'owner'])->assertFailed();
        $this->assertDatabaseHas('users', ['id' => $member->id, 'is_admin' => false, 'is_superadmin' => false]);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'is_admin' => true, 'is_superadmin' => false]);
    }

    public function test_migration_preserves_full_access_for_existing_administrators(): void
    {
        $admin = $this->staff();
        $member = User::factory()->create();
        $migration = require database_path('migrations/2026_10_22_000002_add_superadmin_access_to_users_table.php');

        $migration->down();
        $migration->up();

        $this->assertTrue($admin->fresh()->isSuperAdmin());
        $this->assertFalse($member->fresh()->isSuperAdmin());
        $this->assertDatabaseCount('users', 2);
    }
}
