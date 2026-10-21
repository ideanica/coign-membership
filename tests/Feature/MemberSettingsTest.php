<?php

namespace Tests\Feature;

use App\Mail\EmailChangeCode;
use App\Models\EmailChange;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MemberSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function mailReady(): void
    {
        Mail::fake();
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'smtp.example.test', 'mail.from.address' => 'no-reply@coignitive.test']);
    }

    private function sendCode(User $member, string $email = 'new@example.test'): string
    {
        $this->actingAs($member)->post('/account/email', ['new_email' => $email, 'current_password' => 'password', '_account_settings' => 1, '_settings_action' => 'email'])
            ->assertSessionHasNoErrors()->assertRedirect(route('membership.profile', ['settings' => 1]));
        $message = Mail::sent(EmailChangeCode::class)->last();
        $this->assertTrue($message->hasTo(strtolower(trim($email))));

        return $message->code;
    }

    public function test_profile_settings_modal_contains_security_and_logout_without_navbar_actions(): void
    {
        $member = User::factory()->create();
        $response = $this->actingAs($member)->get('/profile?settings=1')->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertSame(1, $xpath->query('//button[@data-dialog-open="member-settings-dialog"]')->length);
        $this->assertSame(1, $xpath->query('//dialog[@id="member-settings-dialog" and @data-open-on-error]')->length);
        $this->assertSame(1, $xpath->query('//dialog[@id="member-settings-dialog"]//form[@action="'.route('logout').'"]')->length);
        $this->assertSame(0, $xpath->query('//header//form[@action="'.route('logout').'"]')->length);
        $this->assertSame(0, $xpath->query('//header//a[@href="'.route('membership.account.security').'"]')->length);
        $this->get('/account/security')->assertRedirect(route('membership.profile', ['settings' => 1]));
    }

    public function test_request_sends_code_only_to_new_email_and_keeps_old_email_until_verified(): void
    {
        $this->mailReady();
        $member = User::factory()->create(['email' => 'old@example.test']);
        $code = $this->sendCode($member, ' NEW@example.test ');
        $this->assertSame('old@example.test', $member->fresh()->email);
        $pending = EmailChange::sole();
        $this->assertSame('new@example.test', $pending->new_email);
        $this->assertTrue(Hash::check($code, $pending->code_hash));
        $this->assertStringNotContainsString($code, $pending->toJson());
        $this->get('/profile?settings=1')->assertOk()->assertSee('new@example.test')->assertSee('Menunggu verifikasi');
        Mail::assertSent(EmailChangeCode::class, 1);
    }

    public function test_verified_change_is_single_use_unlinks_google_and_ignores_forged_email(): void
    {
        $this->mailReady();
        $member = User::factory()->create(['email' => 'old@example.test', 'google_id' => 'old-google-account']);
        $code = $this->sendCode($member);
        $this->put('/account/email', ['verification_code' => $code, 'new_email' => 'forged@example.test'])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('new@example.test', $member->fresh()->email);
        $this->assertNotNull($member->fresh()->email_verified_at);
        $this->assertNull($member->fresh()->google_id);
        $this->assertDatabaseCount('email_changes', 0);
        $this->put('/account/email', ['verification_code' => $code])->assertSessionHasErrors('verification_code');
    }

    public function test_email_changes_require_current_password_and_reject_existing_email(): void
    {
        $this->mailReady();
        $member = User::factory()->create();
        User::factory()->create(['email' => 'taken@example.test']);
        $this->actingAs($member)->post('/account/email', ['new_email' => 'new@example.test'])->assertSessionHasErrors('current_password');
        $this->post('/account/email', ['new_email' => 'new@example.test', 'current_password' => 'wrong'])->assertSessionHasErrors('current_password');
        $this->post('/account/email', ['new_email' => 'taken@example.test', 'current_password' => 'password'])->assertSessionHasErrors('new_email');
        $this->post('/account/email', ['new_email' => 'new@example.test', 'current_password' => ['bad']])->assertSessionHasErrors('current_password');
        $this->followingRedirects()->from('/profile?settings=1')->post('/account/email', ['new_email' => ['bad'], 'current_password' => 'password', '_account_settings' => 1, '_settings_action' => 'email'])->assertOk()->assertSee('Email baru harus berupa teks.');
        $this->assertDatabaseCount('email_changes', 0);
        Mail::assertNothingSent();
    }

    public function test_wrong_code_limit_is_persisted_and_correct_code_cannot_bypass_it(): void
    {
        $this->mailReady();
        $member = User::factory()->create();
        $originalEmail = $member->email;
        $code = $this->sendCode($member);
        $this->put('/account/email', ['verification_code' => ['bad']])->assertSessionHasErrors('verification_code');
        $this->assertSame(0, EmailChange::sole()->attempts);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->put('/account/email', ['verification_code' => '000000'])->assertSessionHasErrors('verification_code');
        }
        $this->assertSame(5, EmailChange::sole()->attempts);
        $this->put('/account/email', ['verification_code' => $code])->assertSessionHasErrors('verification_code');
        $this->assertSame($originalEmail, $member->fresh()->email);
    }

    public function test_expired_code_cannot_change_email(): void
    {
        $this->freezeTime();
        $this->mailReady();
        $member = User::factory()->create();
        $originalEmail = $member->email;
        $code = $this->sendCode($member);
        $this->travel(10)->minutes();
        $this->put('/account/email', ['verification_code' => $code])->assertSessionHasErrors('verification_code');
        $this->assertSame($originalEmail, $member->fresh()->email);
        $this->assertDatabaseCount('email_changes', 0);
    }

    public function test_resend_obeys_cooldown_replaces_code_and_stays_with_the_pending_email(): void
    {
        $this->freezeTime();
        $this->mailReady();
        $member = User::factory()->create();
        $this->sendCode($member);
        $hash = EmailChange::sole()->code_hash;
        $this->post('/account/email/resend')->assertSessionHasErrors('new_email');
        Mail::assertSent(EmailChangeCode::class, 1);
        $this->travel(61)->seconds();
        $this->post('/account/email/resend', ['new_email' => 'forged@example.test'])->assertSessionHasNoErrors()->assertRedirect();
        Mail::assertSent(EmailChangeCode::class, 2);
        $this->assertNotSame($hash, EmailChange::sole()->code_hash);
        $this->assertSame('new@example.test', EmailChange::sole()->new_email);
        $this->assertTrue(Mail::sent(EmailChangeCode::class)->last()->hasTo('new@example.test'));
    }

    public function test_another_login_session_or_member_cannot_use_the_code(): void
    {
        $this->mailReady();
        $member = User::factory()->create();
        $email = $member->email;
        $code = $this->sendCode($member);
        $this->actingAs(User::factory()->create())->put('/account/email', ['verification_code' => $code])->assertSessionHasErrors('verification_code');
        $this->assertSame($email, $member->fresh()->email);
        $this->actingAs($member);
        $this->withSession(['email_change_token' => 'different-login-session']);
        $this->put('/account/email', ['verification_code' => $code])->assertSessionHasErrors('verification_code');
        $this->assertSame($email, $member->fresh()->email);
    }

    public function test_unconfigured_or_failed_delivery_never_claims_a_code_was_sent(): void
    {
        Mail::fake();
        config(['mail.default' => 'log']);
        $member = User::factory()->create();
        $this->actingAs($member)->post('/account/email', ['new_email' => 'new@example.test', 'current_password' => 'password'])->assertSessionHasErrors('new_email');
        $this->assertDatabaseCount('email_changes', 0);
        Mail::assertNothingSent();
    }

    public function test_mail_transport_failure_rolls_back_pending_change(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'smtp.example.test', 'mail.from.address' => 'no-reply@coignitive.test']);
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('Mail unavailable'));
        $member = User::factory()->create();
        $this->actingAs($member)->post('/account/email', ['new_email' => 'new@example.test', 'current_password' => 'password'])->assertSessionHasErrors('new_email');
        $this->assertDatabaseCount('email_changes', 0);
    }

    public function test_email_taken_after_code_was_sent_cannot_be_overwritten(): void
    {
        $this->mailReady();
        $member = User::factory()->create();
        $email = $member->email;
        $code = $this->sendCode($member);
        User::factory()->create(['email' => 'new@example.test']);
        $this->put('/account/email', ['verification_code' => $code])->assertSessionHasErrors('verification_code');
        $this->assertSame($email, $member->fresh()->email);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_google_only_member_can_verify_new_email_then_must_set_password(): void
    {
        $this->mailReady();
        $member = User::factory()->create(['password_setup_at' => null, 'google_id' => 'old-google']);
        $this->actingAs($member)->withSession(['auth_via_google' => true])->post('/account/email', ['new_email' => 'new@example.test'])->assertSessionHasNoErrors();
        $code = Mail::sent(EmailChangeCode::class)->sole()->code;
        $this->put('/account/email', ['verification_code' => $code])->assertRedirect('/account/setup');
        $this->assertSame('new@example.test', $member->fresh()->email);
        $this->assertNull($member->fresh()->google_id);
        $this->assertFalse((bool) session('auth_via_google'));
    }

    public function test_password_change_returns_to_modal_and_invalidates_pending_email_code(): void
    {
        $this->mailReady();
        $member = User::factory()->create();
        $this->sendCode($member);
        $this->put('/account/password', ['current_password' => 'password', 'password' => 'new-password', 'password_confirmation' => 'new-password'])->assertSessionHasNoErrors()->assertRedirect(route('membership.profile', ['settings' => 1]));
        $this->assertDatabaseCount('email_changes', 0);
        $this->assertTrue(Hash::check('new-password', $member->fresh()->password));
    }

    public function test_guests_and_staff_cannot_use_member_email_change_routes(): void
    {
        $this->mailReady();
        $this->post('/account/email')->assertRedirect('/login');
        foreach ([false, true] as $superadmin) {
            $staff = User::factory()->create(['is_admin' => true, 'is_superadmin' => $superadmin]);
            $this->actingAs($staff)->post('/account/email')->assertRedirect('/admin');
            $this->put('/account/email')->assertRedirect('/admin');
        }
        $this->assertDatabaseCount('email_changes', 0);
        Mail::assertNothingSent();
    }

    public function test_email_send_limit_is_shared_between_send_and_resend(): void
    {
        $this->freezeTime();
        $this->mailReady();
        $member = User::factory()->create();
        $this->sendCode($member);
        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->travel(61)->seconds();
            $this->post('/account/email/resend')->assertSessionHasNoErrors();
        }
        $this->travel(61)->seconds();
        $this->post('/account/email/resend')->assertTooManyRequests();
        Mail::assertSent(EmailChangeCode::class, 5);
    }

    public function test_email_content_is_localized_and_contains_single_use_instructions(): void
    {
        $mail = new EmailChangeCode('123456');
        $mail->locale('en');
        $mail->assertSeeInHtml('123456');
        $mail->assertSeeInText('10 minutes');
        $mail->assertSeeInText('Do not share');
    }

    public function test_successful_change_ends_other_database_sessions_and_reset_tokens(): void
    {
        config(['session.driver' => 'database']);
        $this->mailReady();
        $member = User::factory()->create();
        $oldEmail = $member->email;
        $code = $this->sendCode($member);
        DB::table('sessions')->insert(['id' => 'other-device', 'user_id' => $member->id, 'payload' => '', 'last_activity' => now()->timestamp]);
        DB::table('password_reset_tokens')->insert(['email' => $oldEmail, 'token' => 'old-token', 'created_at' => now()]);
        $this->put('/account/email', ['verification_code' => $code])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('sessions', ['id' => 'other-device']);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $oldEmail]);
    }
}
