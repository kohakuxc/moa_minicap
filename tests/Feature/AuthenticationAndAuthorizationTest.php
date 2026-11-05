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
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated();
        $this->assertAuthenticated();
        $user = User::where('email', 'jane@example.com')->firstOrFail();
        $this->assertSame('patient', $user->role);
        $this->assertTrue(Hash::check('password123', $user->password));
        Notification::assertSentTo($user, \Illuminate\Auth\Notifications\VerifyEmail::class);
    }

    public function test_password_reset_request_is_generic(): void
    {
        $this->postJson('/auth/forgot-password', ['email' => 'missing@example.com'])
            ->assertOk()
            ->assertJson(['message' => 'If an account exists, reset instructions will be sent.']);
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
