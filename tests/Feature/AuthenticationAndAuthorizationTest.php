<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DoctorAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationAndAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_register_and_password_is_hashed(): void
    {
        Notification::fake();

        $response = $this->postJson('/auth/register', [
            'name' => 'Jane Patient',
            'email' => 'jane@example.com',
            'phone' => '555-0100',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
        ]);

        $response->assertCreated();
        $this->assertAuthenticated();
        $user = User::where('email', 'jane@example.com')->firstOrFail();
        $this->assertSame('patient', $user->role);
        $this->assertTrue(Hash::check('Password123!', $user->password));
        Notification::assertSentTo($user, \Illuminate\Auth\Notifications\VerifyEmail::class);
    }

    public function test_registration_succeeds_gracefully_when_verification_email_fails(): void
    {
        Notification::shouldReceive('send')
            ->once()
            ->andThrow(new \RuntimeException('SMTP connection timed out'));

        $response = $this->post('/auth/register', [
            'name' => 'Grace Patient',
            'email' => 'grace@example.com',
            'phone' => '555-0199',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
        ]);

        $response->assertRedirect('/email/verify');
        $response->assertSessionHas('warning');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'grace@example.com']);
    }

    public function test_resend_verification_returns_503_when_mailer_fails(): void
    {
        $user = User::factory()->unverified()->create();

        Notification::shouldReceive('send')
            ->once()
            ->andThrow(new \RuntimeException('Mailer unavailable'));

        $this->actingAs($user)
            ->postJson('/email/verification-notification')
            ->assertStatus(503)
            ->assertJsonStructure(['message']);
    }

    public function test_resend_mailer_transport_can_be_resolved(): void
    {
        config([
            'mail.default' => 'resend',
            'services.resend.key' => 're_test_1234567890',
        ]);

        $transport = app('mail.manager')->mailer('resend')->getSymfonyTransport();

        $this->assertInstanceOf(\Illuminate\Mail\Transport\ResendTransport::class, $transport);
    }

    public function test_brevo_mailer_sends_email_via_http_api(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'https://api.brevo.com/v3/smtp/email' => \Illuminate\Support\Facades\Http::response([
                'messageId' => '<brevo-test-123>',
            ], 201),
        ]);

        config([
            'mail.default' => 'brevo',
            'services.brevo.key' => 'xkeysib-test-key',
            'mail.from.address' => 'clinic@example.com',
            'mail.from.name' => 'MOA Optical Clinic',
        ]);

        $transport = app('mail.manager')->mailer('brevo')->getSymfonyTransport();
        $this->assertInstanceOf(\App\Mail\Transport\BrevoTransport::class, $transport);

        \Illuminate\Support\Facades\Mail::mailer('brevo')->raw('Verify your account', function ($message): void {
            $message->to('patient@example.com', 'Patient Name')->subject('Verify Email');
        });

        \Illuminate\Support\Facades\Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.brevo.com/v3/smtp/email'
                && $request->header('api-key')[0] === 'xkeysib-test-key'
                && $request['sender']['email'] === 'clinic@example.com'
                && $request['to'][0]['email'] === 'patient@example.com'
                && $request['subject'] === 'Verify Email';
        });
    }

    public function test_brevo_mailer_throws_transport_exception_on_api_error(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'https://api.brevo.com/v3/smtp/email' => \Illuminate\Support\Facades\Http::response([
                'message' => 'Key not found',
            ], 401),
        ]);

        config([
            'mail.default' => 'brevo',
            'services.brevo.key' => 'invalid-key',
            'mail.from.address' => 'clinic@example.com',
        ]);

        $this->expectException(\Symfony\Component\Mailer\Exception\TransportException::class);

        \Illuminate\Support\Facades\Mail::mailer('brevo')->raw('Hello', function ($message): void {
            $message->to('patient@example.com')->subject('Test');
        });
    }

    public function test_registration_requires_terms_and_a_strong_password(): void
    {
        Notification::fake();

        $this->postJson('/auth/register', [
            'name' => 'Jane Patient',
            'email' => 'jane-weak@example.com',
            'phone' => '555-0101',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertUnprocessable()->assertJsonValidationErrors(['password', 'terms']);
    }

    public function test_registration_rejects_a_duplicate_mobile_number(): void
    {
        Notification::fake();
        User::factory()->create(['phone' => '555-0102']);

        $this->postJson('/auth/register', [
            'name' => 'Another Patient',
            'email' => 'another@example.com',
            'phone' => ' 555-0102 ',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
        ])->assertUnprocessable()->assertJsonValidationErrors(['phone']);
    }

    public function test_completed_patient_login_goes_to_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'complete@example.com',
            'password' => 'Password123!',
            'profile_completed_at' => now(),
        ]);

        $this->postJson('/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ])->assertOk()->assertJsonPath('redirect', '/dashboard');
    }

    public function test_completed_patient_cannot_reopen_profile_setup(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'profile_completed_at' => now(),
        ]);

        $this->actingAs($user)->get('/profile/create')->assertRedirect('/dashboard');
    }

    public function test_password_reset_request_is_generic(): void
    {
        $this->postJson('/auth/forgot-password', ['email' => 'missing@example.com'])
            ->assertOk()
            ->assertJson(['message' => 'If an account exists with that email, a reset link has been sent.']);
    }

    public function test_patient_cannot_access_admin_area(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'patient', 'email_verified_at' => now()]))
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_patient_records_are_isolated(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $other = User::factory()->create(['email_verified_at' => now()]);
        $appointment = $other->appointments()->create([
            'service' => 'Exam',
            'starts_at' => now()->addDay(),
        ]);

        $this->actingAs($owner)->delete("/appointments/{$appointment->id}")->assertNotFound();
    }

    public function test_verified_patient_can_request_an_appointment(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->postJson('/appointments', [
            'service' => 'Comprehensive eye exam',
            'starts_at' => now()->addWeek()->toDateTimeString(),
        ])->assertCreated();

        $this->assertDatabaseHas('appointments', [
            'user_id' => $user->id,
            'service' => 'Comprehensive eye exam',
            'status' => 'requested',
        ]);
    }

    public function test_doctor_can_publish_availability_and_manage_requests(): void
    {
        $doctor = User::factory()->create([
            'role' => 'doctor',
            'email_verified_at' => now(),
        ]);
        $patient = User::factory()->create(['email_verified_at' => now()]);
        $startsAt = now()->addWeek()->nextWeekday()->setTime(9, 0);

        $this->actingAs($doctor)->post('/admin/availability', [
            'starts_at' => $startsAt->toDateTimeString(),
            'ends_at' => $startsAt->copy()->addHours(8)->toDateTimeString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('doctor_availabilities', ['doctor_id' => $doctor->id]);

        $this->actingAs($patient)->postJson('/appointments', [
            'service' => 'Comprehensive eye exam',
            'starts_at' => $startsAt->toDateTimeString(),
        ])->assertCreated();

        $appointment = $patient->appointments()->firstOrFail();
        $this->actingAs($doctor)->patchJson("/admin/appointments/{$appointment->id}", [
            'status' => 'confirmed',
        ])->assertRedirect();

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'confirmed']);
    }
}
