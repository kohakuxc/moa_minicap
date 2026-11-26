<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\ClinicService;
use App\Models\DigitalIntake;
use App\Models\DoctorAvailability;
use App\Models\DoctorLeaveBlock;
use App\Models\User;
use App\Support\BookingRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function store(Request $request)
    {
        abort_unless($request->user()->profile_completed_at, 403, 'Complete your profile before booking an appointment.');

        $validated = $request->validate([
            'service' => ['nullable', 'string', 'max:100'],
            'service_id' => ['nullable', 'exists:clinic_services,id'],
            'doctor_id' => ['nullable', 'exists:users,id'],
            'starts_at' => ['required', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
            // Digital Pre-Visit Intake fields
            'symptoms' => ['nullable', 'string', 'max:1000'],
            'medical_history' => ['nullable', 'string', 'max:1000'],
            'ocular_history' => ['nullable', 'string', 'max:1000'],
            'allergies' => ['nullable', 'string', 'max:500'],
            'hmo_provider_name' => ['nullable', 'string', 'max:120'],
            'hmo_member_id' => ['nullable', 'string', 'max:100'],
            'preferred_eyewear' => ['nullable', 'string', 'max:60'],
            // One-appointment policy: the patient agrees to cancel the previous booking.
            'replace_existing' => ['nullable', 'boolean'],
            'cancellation_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $startsAt = now()->parse($validated['starts_at']);
        $reason = BookingRules::unavailableReason($startsAt->copy()->setTimezone(config('app.timezone'))->toDateString());
        if ($reason) {
            throw ValidationException::withMessages(['starts_at' => $reason]);
        }

        // One active appointment per account.
        $existing = BookingRules::activeAppointmentFor($request->user());
        $cancelReason = trim((string) ($validated['cancellation_reason'] ?? ''));
        if ($existing) {
            if (! $request->boolean('replace_existing')) {
                $message = BookingRules::oneAppointmentPolicy();

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => $message,
                        'code' => 'existing_appointment',
                        'appointment' => [
                            'id' => $existing->id,
                            'service' => $existing->service,
                            'when' => $existing->starts_at->format('l, F j, Y \a\t g:i A'),
                        ],
                    ], 409);
                }

                return back()->withInput()->with('existing_appointment', true)->withErrors(['starts_at' => $message]);
            }

            if (mb_strlen($cancelReason) < BookingRules::CANCELLATION_REASON_MIN) {
                throw ValidationException::withMessages(['cancellation_reason' => 'Please tell us why you are cancelling your previous appointment (at least '.BookingRules::CANCELLATION_REASON_MIN.' characters).']);
            }
        }

        $clinicService = ! empty($validated['service_id'])
            ? ClinicService::find($validated['service_id'])
            : null;

        $serviceName = $clinicService ? $clinicService->name : ($validated['service'] ?? 'Comprehensive Eye Exam');
        $durationMinutes = $clinicService ? $clinicService->duration_minutes : 30;

        $doctor = ! empty($validated['doctor_id'])
            ? User::find($validated['doctor_id'])
            : User::whereIn('role', [User::ROLE_OPTOMETRIST, User::ROLE_DOCTOR, User::ROLE_ADMIN])->first();

        // Check if doctor has a leave block
        if ($doctor) {
            $onLeave = DoctorLeaveBlock::where('doctor_id', $doctor->id)
                ->where('starts_at', '<=', $startsAt)
                ->where('ends_at', '>=', $startsAt)
                ->exists();
            abort_if($onLeave, 422, 'The selected specialist is on approved leave at that time. Please pick another date or specialist.');
        }

        $endsAt = $startsAt->copy()->addMinutes($durationMinutes);

        $appointment = DB::transaction(function () use ($request, $validated, $startsAt, $endsAt, $doctor, $clinicService, $serviceName, $durationMinutes, $existing, $cancelReason) {
            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::select('select pg_advisory_xact_lock(hashtext(?))', ['patient-appointment-'.$request->user()->id]);
                DB::select('select pg_advisory_xact_lock(hashtext(?))', ['booking-day-'.$startsAt->toDateString()]);
            }

            // Re-check inside the lock so a double-click cannot create two active appointments.
            $stillActive = BookingRules::activeAppointmentFor($request->user());
            if ($stillActive && ! $existing) {
                throw ValidationException::withMessages(['starts_at' => BookingRules::oneAppointmentPolicy()]);
            }

            if ($existing && $stillActive) {
                $stillActive->update(['status' => 'cancelled', 'reason' => $cancelReason]);
                AuditLog::record($request->user(), 'appointment.cancelled_by_patient_to_rebook', $stillActive);
            }

            // Re-check the daily cap inside the lock so two simultaneous requests cannot exceed it.
            if (BookingRules::bookedCount($startsAt) >= BookingRules::DAILY_LIMIT) {
                throw ValidationException::withMessages(['starts_at' => BookingRules::unavailableReason($startsAt->toDateString()) ?? 'This date is fully booked.']);
            }

            $conflict = Appointment::whereIn('status', ['requested', 'confirmed', 'in_progress'])
                ->where(function ($q) use ($doctor) {
                    if ($doctor) {
                        $q->where('doctor_id', $doctor->id);
                    }
                })
                ->where('starts_at', '<', $endsAt)
                ->where('starts_at', '>=', $startsAt->copy()->subMinutes(60))
                ->lockForUpdate()
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages(['starts_at' => 'That time is already reserved. Please choose another time.']);
            }

            $apt = $request->user()->appointments()->create([
                'doctor_id' => $doctor?->id,
                'service_id' => $clinicService?->id,
                'service' => $serviceName,
                'specialist' => $doctor?->name,
                'starts_at' => $startsAt,
                'duration_minutes' => $durationMinutes,
                'notes' => $validated['notes'] ?? null,
                'status' => 'requested',
            ]);

            // Create Digital Intake record if patient supplied symptoms/history
            if (! empty($validated['symptoms']) || ! empty($validated['medical_history']) || ! empty($validated['hmo_provider_name'])) {
                DigitalIntake::create([
                    'appointment_id' => $apt->id,
                    'patient_id' => $request->user()->id,
                    'medical_history' => $validated['medical_history'] ?? null,
                    'symptoms' => $validated['symptoms'] ?? null,
                    'ocular_history' => $validated['ocular_history'] ?? null,
                    'allergies' => $validated['allergies'] ?? null,
                    'hmo_provider_name' => $validated['hmo_provider_name'] ?? null,
                    'hmo_member_id' => $validated['hmo_member_id'] ?? null,
                    'preferred_eyewear' => $validated['preferred_eyewear'] ?? null,
                    'submitted_at' => now(),
                ]);
            }

            AuditLog::record($request->user(), 'appointment.booked_with_intake', $apt);

            return $apt;
        });

        return $request->expectsJson()
            ? response()->json(['message' => 'Appointment request received.', 'id' => $appointment->id], 201)
            : redirect()->route('dashboard')->with('success', 'Appointment successfully requested! Our clinical team has received your pre-visit intake.');
    }

    public function slots(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'doctor_id' => ['nullable', 'exists:users,id'],
        ]);

        $date = now()->parse($validated['date'])->toDateString();
        $doctorId = $validated['doctor_id'] ?? null;

        if ($reason = BookingRules::unavailableReason($date)) {
            return response()->json(['data' => [], 'reason' => $reason]);
        }

        $doctor = $doctorId
            ? User::find($doctorId)
            : User::whereIn('role', [User::ROLE_OPTOMETRIST, User::ROLE_DOCTOR, User::ROLE_ADMIN])->first();

        $slots = collect();

        if ($doctor) {
            $slots = DoctorAvailability::where('doctor_id', $doctor->id)
                ->where('is_available', true)
                ->whereDate('starts_at', $date)
                ->orderBy('starts_at')
                ->get()
                ->filter(function (DoctorAvailability $availability) use ($doctor) {
                    return ! Appointment::where('doctor_id', $doctor->id)
                        ->where('starts_at', $availability->starts_at)
                        ->whereNotIn('status', ['cancelled', 'rejected'])
                        ->exists();
                })
                ->map(fn (DoctorAvailability $availability) => [
                    'value' => $availability->starts_at->toDateTimeString(),
                    'label' => $availability->starts_at->format('g:i A'),
                ])->values();
        }

        // Fallback default slots if doctor has open schedule today (9:00 AM - 5:00 PM)
        if ($slots->isEmpty()) {
            $times = ['09:00:00', '10:00:00', '11:00:00', '13:00:00', '14:00:00', '15:00:00', '16:00:00'];
            $slots = collect($times)->map(function ($time) use ($date) {
                $dt = now()->parse("{$date} {$time}");
                return [
                    'value' => $dt->toDateTimeString(),
                    'label' => $dt->format('g:i A'),
                ];
            });
        }

        return response()->json(['data' => $slots]);
    }
}
