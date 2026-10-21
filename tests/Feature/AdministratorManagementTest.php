<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdministratorManagementTest extends TestCase
{
    use RefreshDatabase;

    private function staff(bool $superadmin = false): User
    {
        return User::factory()->create(['is_admin' => true, 'is_superadmin' => $superadmin]);
    }

    public function test_only_superadmins_can_view_create_or_change_administrator_access(): void
    {
        $administrator = $this->staff();
        $member = User::factory()->create();

        foreach ([$administrator, $member] as $user) {
            $this->actingAs($user)->get('/admin/administrators')->assertForbidden();
            $this->post('/admin/administrators', ['name' => 'Unauthorized admin', 'email' => 'bad@example.com', 'password' => 'valid-password-123'])->assertForbidden();
            $this->patch('/admin/administrators/'.$administrator->id.'/access', ['is_active' => 0])->assertForbidden();
        }

        $this->assertDatabaseCount('users', 2);
        $this->assertTrue($administrator->fresh()->is_active);
    }

    public function test_superadmin_lists_only_regular_admins_and_can_filter_them(): void
    {
        $superadmin = $this->staff(true);
        $admin = $this->staff();
        $inactive = $this->staff();
        $inactive->forceFill(['is_active' => false])->save();
        User::factory()->create();

        $response = $this->actingAs($superadmin)->get('/admin/administrators')->assertOk()
            ->assertViewHas('administrators', fn ($rows) => $rows->total() === 2)
            ->assertSee('Manajemen admin');
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertSame(1, $xpath->query('//dialog[@id="administrator-create-dialog"]//form')->length);
        $this->assertSame(0, $xpath->query('//dialog[@id="administrator-create-dialog"]//select[@name="role"]')->length);

        $this->get('/admin/administrators?access=active&q='.urlencode($admin->email))
            ->assertOk()->assertViewHas('administrators', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $admin->id);
    }

    public function test_superadmin_creates_a_hashed_regular_admin_even_if_role_is_forged(): void
    {
        $superadmin = $this->staff(true);
        $password = 'new-admin-password-123';

        $this->actingAs($superadmin)->post('/admin/administrators', [
            'name' => 'Operations Admin', 'email' => '  OPERATIONS@example.com  ', 'password' => $password,
            'role' => 'superadmin', 'is_superadmin' => true, 'is_admin' => false, 'is_active' => false,
        ])->assertSessionHasNoErrors()->assertRedirect('/admin/administrators');

        $administrator = User::where('email', 'operations@example.com')->firstOrFail();
        $this->assertTrue($administrator->is_admin);
        $this->assertFalse($administrator->is_superadmin);
        $this->assertTrue($administrator->is_active);
        $this->assertTrue(Hash::check($password, $administrator->password));
        $this->assertNotNull($administrator->password_setup_at);
        $this->post('/logout');
        $this->post('/login', ['email' => $administrator->email, 'password' => $password])->assertRedirect('/admin');
        $this->get('/admin/tracking')->assertOk()->assertDontSee('Manajemen admin');
        $this->get('/admin/members')->assertForbidden();
    }

    public function test_duplicate_emails_and_malformed_credentials_are_rejected(): void
    {
        $member = User::factory()->create();
        $this->actingAs($this->staff(true));

        $this->post('/admin/administrators', ['name' => 'Duplicate', 'email' => $member->email, 'password' => 'valid-password-123'])
            ->assertSessionHasErrors('email');
        $this->post('/admin/administrators', ['name' => '', 'email' => ['malformed'], 'password' => 'short'])
            ->assertSessionHasErrors(['name', 'email', 'password']);
        $this->assertDatabaseCount('users', 2);
        $this->assertFalse($member->fresh()->is_admin);
    }

    public function test_validation_reopens_creation_dialog_without_refilling_password(): void
    {
        $this->actingAs($this->staff(true));

        $this->followingRedirects()->from('/admin/administrators')->post('/admin/administrators', [
            '_workspace_form' => 'administrator-create', 'name' => 'Remember this name', 'email' => 'invalid-email', 'password' => 'private-password-123',
        ])->assertOk()->assertSee('data-open-on-error', false)->assertSee('Remember this name')->assertDontSee('private-password-123');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_superadmin_can_disable_and_reenable_admin_but_cannot_modify_superadmins_or_members(): void
    {
        $superadmin = $this->staff(true);
        $administrator = $this->staff();
        $member = User::factory()->create();
        $this->actingAs($superadmin);

        $this->patch('/admin/administrators/'.$administrator->id.'/access', ['is_active' => 0])->assertRedirect('/admin/administrators');
        $this->assertFalse($administrator->fresh()->is_active);
        $this->patch('/admin/administrators/'.$administrator->id.'/access', ['is_active' => 1])->assertRedirect('/admin/administrators');
        $this->assertTrue($administrator->fresh()->is_active);
        foreach ([$superadmin, $member] as $protected) {
            $this->patch('/admin/administrators/'.$protected->id.'/access', ['is_active' => 0])->assertNotFound();
            $this->assertTrue($protected->fresh()->is_active);
        }
    }

    public function test_invalid_access_status_does_not_change_the_account(): void
    {
        $administrator = $this->staff();

        $this->actingAs($this->staff(true))->patch('/admin/administrators/'.$administrator->id.'/access', ['is_active' => 'invalid'])
            ->assertSessionHasErrors('is_active');
        $this->assertTrue($administrator->fresh()->is_active);
    }

    public function test_deactivated_admin_cannot_keep_an_authenticated_session(): void
    {
        $administrator = $this->staff();

        $this->actingAs($this->staff(true))->patch('/admin/administrators/'.$administrator->id.'/access', ['is_active' => 0])->assertRedirect();
        $this->actingAs($administrator->fresh())->get('/admin')->assertRedirect('/login');
        $this->assertGuest();
    }
}
