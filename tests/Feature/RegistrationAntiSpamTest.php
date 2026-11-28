<?php

namespace Tests\Feature;

use App\Models\PendingRegistration;
use App\Models\User;
use App\Notifications\PendingRegistrationVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationAntiSpamTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_registration_creates_user_only_after_email_verification_without_calling_sms(): void
    {
        Notification::fake();
        Http::fake();
        $capturedUrl = null;

        $response = $this->post('/auth/register', [
            'name' => 'Sofia Mercado',
            'date_of_birth' => '1996-03-18',
            'email' => 'sofia.mercado+test@gmail.com',
            'phone' => '0917 888 1234',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('registration.verify.notice'));
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        Http::assertNothingSent();

        Notification::assertSentTo(
            new AnonymousNotifiable,
            PendingRegistrationVerifyEmail::class,
            function (PendingRegistrationVerifyEmail $notification) use (&$capturedUrl): bool {
                $capturedUrl = $notification->verificationUrl;

                return true;
            }
        );

        $pending = PendingRegistration::firstOrFail();

        // Notice page should not display SMS OTP section when SMS_OTP_ENABLED=false
        $this->get('/register/verify?token='.$pending->token)
            ->assertOk()
            ->assertDontSee('Enter 6-digit SMS code')
            ->assertSee('Resend email verification link');

        // Clicking email link immediately creates User and logs in
        $this->get($capturedUrl)->assertRedirect(route('profile.create'));
        $this->assertAuthenticated();
        $this->assertDatabaseCount('pending_registrations', 0);

        $user = User::where('email', 'sofia.mercado+test@gmail.com')->firstOrFail();
        $this->assertSame('sofiamercado@gmail.com', $user->canonical_email);
        $this->assertSame('+639178881234', $user->phone);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->phone_verified_at);
    }

    public function test_full_registration_requires_both_email_and_sms_otp_before_creating_user(): void
    {
        Notification::fake();
        $capturedOtp = null;
        $capturedUrl = null;

        Http::fake([
            'https://api.brevo.com/v3/transactionalSMS/sms' => function ($request) use (&$capturedOtp) {
                if (preg_match('/\b(\d{6})\b/', (string) $request['content'], $matches)) {
                    $capturedOtp = $matches[1];
                }

                return Http::response(['messageId' => 12345], 201);
            },
        ]);

        config([
            'services.sms.otp_enabled' => true,
            'services.sms.driver' => 'brevo',
            'services.brevo.key' => 'xkeysib-test-sms',
            'services.brevo.sms_sender' => 'MOAClinic',
        ]);

        $response = $this->post('/auth/register', [
            'name' => 'Juan Dela Cruz',
            'date_of_birth' => '1993-07-21',
            'email' => 'juan.delacruz+clinic@gmail.com',
            'phone' => '0917 123 4567',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('registration.verify.notice'));
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        $this->assertNotNull($capturedOtp);

        Notification::assertSentTo(
            new AnonymousNotifiable,
            PendingRegistrationVerifyEmail::class,
            function (PendingRegistrationVerifyEmail $notification) use (&$capturedUrl): bool {
                $capturedUrl = $notification->verificationUrl;

                return true;
            }
        );

        $pending = PendingRegistration::firstOrFail();
        $this->assertSame('+639171234567', $pending->phone);
        $this->assertSame('juandelacruz@gmail.com', $pending->canonical_email);

        // Step 1: Verify SMS OTP only -> still pending, no user created yet
        $this->post('/register/verify-otp', [
            'token' => $pending->token,
            'otp' => $capturedOtp,
        ])->assertRedirect(route('registration.verify.notice'));

        $this->assertDatabaseCount('users', 0);
        $this->assertNotNull($pending->fresh()->phone_verified_at);

        // Step 2: Click signed email verification link -> user is created & logged in
        $this->get($capturedUrl)
            ->assertRedirect(route('profile.create'));

        $this->assertAuthenticated();
        $this->assertDatabaseCount('pending_registrations', 0);

        $user = User::where('email', 'juan.delacruz+clinic@gmail.com')->firstOrFail();
        $this->assertSame('juandelacruz@gmail.com', $user->canonical_email);
        $this->assertSame('+639171234567', $user->phone);
        $this->assertSame('1993-07-21', $user->date_of_birth->format('Y-m-d'));
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->phone_verified_at);
    }

    public function test_turnstile_captcha_blocks_missing_or_invalid_token_and_accepts_valid_token(): void
    {
        Notification::fake();

        config([
            'services.turnstile.enabled' => true,
            'services.turnstile.site_key' => 'site-key-test',
            'services.turnstile.secret_key' => 'secret-key-test',
        ]);

        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::sequence()
                ->push(['success' => false], 200)
                ->push(['success' => true], 200),
        ]);

        $payload = [
            'name' => 'Maria Clara',
            'date_of_birth' => '1998-02-14',
            'email' => 'maria@example.com',
            'phone' => '09181112222',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
        ];

        // Missing token
        $this->postJson('/auth/register', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cf-turnstile-response']);

        // Failed Turnstile response
        $this->postJson('/auth/register', array_merge($payload, ['cf-turnstile-response' => 'bad-token']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cf-turnstile-response']);

        // Valid Turnstile response
        $this->postJson('/auth/register', array_merge($payload, ['cf-turnstile-response' => 'good-token']))
            ->assertStatus(202);
    }

    public function test_blocks_gmail_dot_and_plus_aliases_and_phone_format_variations(): void
    {
        Notification::fake();

        User::factory()->create([
            'name' => 'Existing User',
            'email' => 'john.doe@gmail.com',
            'phone' => '+639178889999',
            'date_of_birth' => '1990-01-01',
        ]);

        // Gmail +tag and dot bypass attempt
        $this->postJson('/auth/register', [
            'name' => 'Different Person',
            'date_of_birth' => '1991-05-05',
            'email' => 'johndoe+spam@googlemail.com',
            'phone' => '09170001111',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email']);

        // Phone formatting bypass attempt (0917-888-9999 vs +639178889999)
        $this->postJson('/auth/register', [
            'name' => 'Different Person',
            'date_of_birth' => '1991-05-05',
            'email' => 'brandnew@example.com',
            'phone' => '0917-888-9999',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
        ])->assertUnprocessable()->assertJsonValidationErrors(['phone']);
    }

    public function test_blocks_duplicate_name_and_date_of_birth_with_recovery_prompt(): void
    {
        Notification::fake();

        User::factory()->create([
            'name' => 'Carlos Mendoza',
            'email' => 'carlos.real@example.com',
            'phone' => '+639171112222',
            'date_of_birth' => '1988-11-30',
        ]);

        // Same name (different casing/spacing) + same DOB + different email & phone
        $this->postJson('/auth/register', [
            'name' => '  carlos   mendoza ',
            'date_of_birth' => '1988-11-30',
            'email' => 'carlos.spam@example.com',
            'phone' => '09189998888',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
        ])->assertUnprocessable()->assertJsonValidationErrors(['duplicate_account']);

        $this->assertDatabaseCount('pending_registrations', 0);
    }

    public function test_wrong_otp_locks_after_five_attempts_until_resend(): void
    {
        Notification::fake();

        $pending = PendingRegistration::create([
            'token' => str_repeat('a', 64),
            'name' => 'Test User',
            'email' => 'otp@example.com',
            'canonical_email' => 'otp@example.com',
            'phone' => '+639171230000',
            'date_of_birth' => '1995-01-01',
            'password' => Hash::make('Password123!'),
            'email_token_hash' => hash('sha256', 'email-token'),
            'phone_otp_hash' => Hash::make('654321'),
            'phone_otp_expires_at' => now()->addMinutes(10),
            'phone_otp_attempts' => 0,
            'last_sms_sent_at' => now()->subMinutes(2),
            'last_email_sent_at' => now()->subMinutes(2),
            'expires_at' => now()->addMinutes(60),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/register/verify-otp', [
                'token' => $pending->token,
                'otp' => '111111',
            ])->assertUnprocessable()->assertJsonValidationErrors(['otp']);
        }

        // Even the correct OTP is now locked until resend
        $this->postJson('/register/verify-otp', [
            'token' => $pending->token,
            'otp' => '654321',
        ])->assertUnprocessable()->assertJsonValidationErrors(['otp']);
    }

    public function test_prune_command_deletes_only_expired_pending_registrations(): void
    {
        PendingRegistration::create([
            'token' => str_repeat('e', 64),
            'name' => 'Expired User',
            'email' => 'expired@example.com',
            'canonical_email' => 'expired@example.com',
            'phone' => '+639170000001',
            'date_of_birth' => '1995-01-01',
            'password' => Hash::make('Password123!'),
            'email_token_hash' => hash('sha256', 't1'),
            'phone_otp_hash' => Hash::make('123456'),
            'phone_otp_expires_at' => now()->subMinutes(5),
            'expires_at' => now()->subMinute(),
        ]);

        PendingRegistration::create([
            'token' => str_repeat('v', 64),
            'name' => 'Active User',
            'email' => 'active@example.com',
            'canonical_email' => 'active@example.com',
            'phone' => '+639170000002',
            'date_of_birth' => '1995-01-01',
            'password' => Hash::make('Password123!'),
            'email_token_hash' => hash('sha256', 't2'),
            'phone_otp_hash' => Hash::make('123456'),
            'phone_otp_expires_at' => now()->addMinutes(10),
            'expires_at' => now()->addMinutes(50),
        ]);

        $this->artisan('registrations:prune')->assertSuccessful();

        $this->assertDatabaseMissing('pending_registrations', ['email' => 'expired@example.com']);
        $this->assertDatabaseHas('pending_registrations', ['email' => 'active@example.com']);
    }

    public function test_email_first_then_otp_creates_user_and_resend_unlocks_attempts(): void
    {
        Notification::fake();
        $capturedUrl = null;
        $dispatchedOtps = [];

        Http::fake([
            'https://api.brevo.com/v3/transactionalSMS/sms' => function ($request) use (&$dispatchedOtps) {
                if (preg_match('/\b(\d{6})\b/', (string) $request['content'], $matches)) {
                    $dispatchedOtps[] = $matches[1];
                }

                return Http::response(['messageId' => 999], 201);
            },
        ]);

        config([
            'services.sms.otp_enabled' => true,
            'services.sms.driver' => 'brevo',
            'services.brevo.key' => 'xkeysib-test-sms',
        ]);

        $this->post('/auth/register', [
            'name' => 'Elena Reyes',
            'date_of_birth' => '1997-09-12',
            'email' => 'elena@example.com',
            'phone' => '09172223333',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
        ])->assertRedirect(route('registration.verify.notice'));

        Notification::assertSentTo(
            new AnonymousNotifiable,
            PendingRegistrationVerifyEmail::class,
            function (PendingRegistrationVerifyEmail $notification) use (&$capturedUrl): bool {
                $capturedUrl = $notification->verificationUrl;

                return true;
            }
        );

        $pending = PendingRegistration::firstOrFail();

        // Click email link first -> redirects to pending verification page, user not created yet
        $this->get($capturedUrl)->assertRedirect(route('registration.verify.notice'));
        $this->assertDatabaseCount('users', 0);
        $this->assertNotNull($pending->fresh()->email_verified_at);

        // Cooldown prevents immediate resend and renders live countdown attributes
        $this->post('/register/resend-otp', ['token' => $pending->token])
            ->assertRedirect();
        $this->get('/register/verify?token='.$pending->token)
            ->assertOk()
            ->assertSee('data-resend-countdown=', false)
            ->assertSee('data-cooldown-banner="sms"', false);
        $this->postJson('/register/resend-otp', ['token' => $pending->token])->assertStatus(429);

        // After 61 seconds, resend issues a fresh OTP
        $pending->forceFill(['last_sms_sent_at' => now()->subSeconds(61)])->save();
        $this->postJson('/register/resend-otp', ['token' => $pending->token])->assertOk();

        $latestOtp = end($dispatchedOtps);
        $this->assertNotFalse($latestOtp);

        $this->post('/register/verify-otp', [
            'token' => $pending->token,
            'otp' => $latestOtp,
        ])->assertRedirect(route('profile.create'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'elena@example.com',
            'phone' => '+639172223333',
        ]);
    }

    public function test_profile_update_enforces_unique_normalized_phone_and_duplicate_identity(): void
    {
        $existing = User::factory()->create([
            'name' => 'Marco Santos',
            'date_of_birth' => '1991-06-15',
            'phone' => '+639173334444',
            'email' => 'marco@example.com',
        ]);

        $other = User::factory()->create([
            'name' => 'Ana Lopez',
            'date_of_birth' => '1994-01-10',
            'phone' => '+639175556666',
            'email' => 'ana@example.com',
            'profile_completed_at' => null,
        ]);

        // Try taking existing user's phone with different formatting
        $this->actingAs($other)
            ->patch('/profile', [
                'name' => 'Ana Lopez',
                'date_of_birth' => '1994-01-10',
                'email' => 'ana@example.com',
                'phone' => '0917-333-4444',
            ])
            ->assertSessionHasErrors(['phone']);

        // Try taking existing user's (name, date_of_birth)
        $this->actingAs($other)
            ->patch('/profile', [
                'name' => 'marco santos',
                'date_of_birth' => '1991-06-15',
                'email' => 'ana@example.com',
                'phone' => '09175556666',
            ])
            ->assertSessionHasErrors(['name']);
    }

    public function test_cross_device_email_verification_automatically_logs_in_originating_desktop_session(): void
    {
        Notification::fake();
        $capturedUrl = null;

        // 1. Desktop submits registration and keeps its own session + pending_desktop_secret
        $registerResponse = $this->post('/auth/register', [
            'name' => 'Cross Device User',
            'date_of_birth' => '1994-11-11',
            'email' => 'crossdevice@example.com',
            'phone' => '09174445555',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
        ]);
        $registerResponse->assertRedirect(route('registration.verify.notice'))
            ->assertSessionHas('pending_desktop_secret');

        $desktopSecret = $this->app['session']->get('pending_desktop_secret');
        $this->assertNotEmpty($desktopSecret);
        $pending = PendingRegistration::firstOrFail();

        $this->assertSame(
            hash('sha256', $desktopSecret),
            \Illuminate\Support\Facades\Cache::get('pending_desktop:'.$pending->token)
        );

        Notification::assertSentTo(
            new AnonymousNotifiable,
            PendingRegistrationVerifyEmail::class,
            function (PendingRegistrationVerifyEmail $notification) use (&$capturedUrl): bool {
                $capturedUrl = $notification->verificationUrl;

                return true;
            }
        );

        // Before verification, Desktop poll returns pending
        $this->getJson('/register/status?token='.$pending->token)
            ->assertOk()
            ->assertJson(['status' => 'pending', 'email_verified' => false]);

        // 2. Simulate Phone clicking the link with a completely separate session (logged out)
        auth()->logout();
        $this->flushSession();

        $this->get($capturedUrl)->assertRedirect(route('profile.create'));
        $this->assertDatabaseHas('users', ['email' => 'crossdevice@example.com']);

        $handoff = \Illuminate\Support\Facades\Cache::get('pending_handoff:'.$pending->token);
        $this->assertIsArray($handoff);
        $this->assertSame($handoff['secret_hash'], hash('sha256', $desktopSecret));

        // 3. Another random browser WITHOUT the desktop secret cannot claim the login
        auth()->logout();
        $this->flushSession();

        $this->getJson('/register/status?token='.$pending->token)
            ->assertOk()
            ->assertJson(['status' => 'expired']);
        $this->assertGuest();

        // 4. Originating Desktop session (with pending_desktop_secret) polls /register/status -> auto logs in & forwards!
        $this->withSession([
            'pending_registration_token' => $pending->token,
            'pending_desktop_secret' => $desktopSecret,
        ])->getJson('/register/status?token='.$pending->token)
            ->assertOk()
            ->assertJson([
                'status' => 'completed',
                'redirect' => '/profile/create',
            ]);

        $this->assertAuthenticated();
        $this->assertSame('crossdevice@example.com', auth()->user()->email);
    }
}
