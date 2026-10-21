<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MemberAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true, 'is_superadmin' => true])->save();

        return $admin;
    }

    private function configureGoogle(): void
    {
        config([
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);
    }

    private function fakeGoogleProfile(string $email, string $googleId = 'google-user-123'): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'google-access-token',
                'token_type' => 'Bearer',
            ]),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => $googleId,
                'email' => $email,
                'email_verified' => true,
                'name' => 'Google Member',
            ]),
        ]);
        Http::preventStrayRequests();
    }

    private function beginGoogleLogin(): string
    {
        $response = $this->get('/auth/google');
        $response->assertRedirect();
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', (string) $response->headers->get('Location'));

        $state = session('google_oauth_state');
        $this->assertIsString($state);
        $this->assertNotSame('', $state);
        $this->assertIsString(session('google_oauth_code_verifier'));

        return $state;
    }

    public function test_admin_creates_member_with_one_time_temporary_password(): void
    {
        $response = $this->actingAs($this->admin())->post('/admin/members', [
            'name' => 'New Member',
            'email' => 'new.member@example.com',
            'member_tier' => 'Member',
            'is_active' => true,
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/admin/members');
        $response->assertSessionHas('temporary_credentials');

        $member = User::where('email', 'new.member@example.com')->firstOrFail();
        $credentials = session('temporary_credentials');

        $this->assertIsArray($credentials);
        $this->assertSame('new.member@example.com', $credentials['email']);
        $temporaryPassword = Crypt::decryptString($credentials['password']);

        $this->assertTrue(Hash::check($temporaryPassword, $member->password));
        $this->assertNull($member->password_setup_at);
        $this->assertFalse($member->is_admin);
    }

    public function test_temporary_password_login_requires_member_to_create_own_password(): void
    {
        $member = User::factory()->create([
            'email' => 'temporary@example.com',
            'password' => 'temporary-password',
            'password_setup_at' => null,
        ]);

        $this->post('/login', [
            'email' => $member->email,
            'password' => 'temporary-password',
        ])->assertRedirect('/account/setup');

        $this->get('/profile')->assertRedirect('/account/setup');

        $this->put('/account/password', [
            'password' => 'member-owned-password',
            'password_confirmation' => 'member-owned-password',
        ])->assertSessionHasNoErrors()->assertRedirect('/profile');

        $member->refresh();
        $this->assertNotNull($member->password_setup_at);
        $this->assertTrue(Hash::check('member-owned-password', $member->password));
        $this->assertFalse(Hash::check('temporary-password', $member->password));
    }

    public function test_google_login_links_only_an_existing_member_and_invalidates_temporary_password(): void
    {
        $this->configureGoogle();
        $this->fakeGoogleProfile('member@example.com');

        $member = User::factory()->create([
            'email' => 'member@example.com',
            'password' => 'temporary-password',
            'password_setup_at' => null,
            'google_id' => null,
        ]);

        $state = $this->beginGoogleLogin();

        $this->get('/auth/google/callback?state='.urlencode($state).'&code=test-code')
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $member->refresh();
        $this->assertAuthenticatedAs($member);
        $this->assertSame('google-user-123', $member->google_id);
        $this->assertNotNull($member->email_verified_at);
        $this->assertNull($member->password_setup_at);
        $this->assertFalse(Hash::check('temporary-password', $member->password));
        $this->assertTrue((bool) session('auth_via_google'));
        $this->get('/profile')->assertOk();
    }

    public function test_google_login_accepts_the_configured_production_callback_path(): void
    {
        $this->configureGoogle();
        config(['services.google.redirect' => 'https://coignitive.vercel.app/login/google/callback']);
        $this->fakeGoogleProfile('callback.member@example.com');
        $member = User::factory()->create(['email' => 'callback.member@example.com']);
        $state = $this->beginGoogleLogin();

        $this->get('/login/google/callback?state='.urlencode($state).'&code=test-code')
            ->assertSessionHasNoErrors()->assertRedirect('/profile');

        $this->assertAuthenticatedAs($member);
        Http::assertSent(fn ($request) => $request->url() === 'https://oauth2.googleapis.com/token'
            && $request['redirect_uri'] === 'https://coignitive.vercel.app/login/google/callback');
    }

    public function test_google_login_never_creates_an_unregistered_member(): void
    {
        $this->configureGoogle();
        $this->fakeGoogleProfile('unknown@example.com');
        $state = $this->beginGoogleLogin();

        $this->get('/auth/google/callback?state='.urlencode($state).'&code=test-code')
            ->assertRedirect('/login')
            ->assertSessionHasErrors('google');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'unknown@example.com']);
    }

    public function test_inactive_member_cannot_login_with_google(): void
    {
        $this->configureGoogle();
        $this->fakeGoogleProfile('inactive@example.com');

        User::factory()->create([
            'email' => 'inactive@example.com',
            'is_active' => false,
        ]);

        $state = $this->beginGoogleLogin();

        $this->get('/auth/google/callback?state='.urlencode($state).'&code=test-code')
            ->assertRedirect('/login')
            ->assertSessionHasErrors('google');

        $this->assertGuest();
    }

    public function test_google_callback_rejects_an_invalid_oauth_state(): void
    {
        $this->configureGoogle();
        $this->fakeGoogleProfile('member@example.com');
        User::factory()->create(['email' => 'member@example.com']);
        $this->beginGoogleLogin();

        $this->get('/auth/google/callback?state=wrong-state&code=test-code')
            ->assertRedirect('/login')
            ->assertSessionHasErrors('google');

        $this->assertGuest();
        Http::assertNothingSent();
    }

    public function test_changing_member_email_unlinks_the_previous_google_identity(): void
    {
        $member = User::factory()->create([
            'email' => 'old@example.com',
            'google_id' => 'linked-google-id',
        ]);

        $this->actingAs($this->admin())->put('/admin/members/'.$member->id, [
            'name' => $member->name,
            'email' => 'new@example.com',
            'member_tier' => $member->member_tier,
            'is_active' => true,
        ])->assertSessionHasNoErrors()->assertRedirect('/admin/members');

        $member->refresh();
        $this->assertSame('new@example.com', $member->email);
        $this->assertNull($member->google_id);
        $this->assertNull($member->email_verified_at);
    }

    public function test_existing_password_change_requires_current_password(): void
    {
        $member = User::factory()->create([
            'password' => 'current-password',
            'password_setup_at' => now(),
        ]);

        $this->actingAs($member)->put('/account/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-member-password',
            'password_confirmation' => 'new-member-password',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('current-password', $member->fresh()->password));
    }
}
