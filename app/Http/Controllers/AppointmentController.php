<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\DoctorAvailability;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function store(Request $request)
    {
        abort_unless($request->user()->profile_completed_at, 403, 'Complete your profile before booking an appointment.');

        $validated = $request->validate([
            'service' => ['required', 'string', 'max:100'],
            'starts_at' => ['required', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $startsAt = now()->parse($validated['starts_at']);
        abort_if($startsAt->isWeekend(), 422, 'Appointments are available Monday-Friday only.');

        $doctor = User::whereIn('role', ['doctor', 'admin'])->first();
        $hasPublishedAvailability = $doctor
            ? DoctorAvailability::where('doctor_id', $doctor->id)->exists()
            : false;
        abort_unless(
            ! $doctor || ! $hasPublishedAvailability || DoctorAvailability::where('doctor_id', $doctor->id)
                ->where('is_available', true)
                ->where('starts_at', '<=', $startsAt)
                ->where('ends_at', '>=', $startsAt->copy()->addHour())
                ->exists(),
            422,
            'That time is not in the doctor’s availability.'
        );

        $endsAt = $startsAt->copy()->addMinutes(60);
        $appointment = DB::transaction(function () use ($request, $validated, $startsAt, $endsAt, $doctor) {
            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::select('select pg_advisory_xact_lock(hashtext(?))', [$startsAt->format('Y-m-d H:i')]);
            }
            $conflict = Appointment::whereIn('status', ['requested', 'confirmed', 'in_progress'])
                ->where('starts_at', '<', $endsAt)
                ->where('starts_at', '>=', $startsAt->copy()->subMinutes(60))
                ->lockForUpdate()
                ->exists();
            if ($conflict) {
                throw ValidationException::withMessages(['starts_at' => 'That time is already reserved. Please choose another time.']);
            }
            return $request->user()->appointments()->create([
                'doctor_id' => $doctor?->id,
                'service' => $validated['service'],
                'specialist' => $doctor?->name,
                'starts_at' => $startsAt,
                'notes' => $validated['notes'] ?? null,
                'status' => 'requested',
            ]);
        });

        return $request->expectsJson()
            ? response()->json(['message' => 'Appointment request received.', 'id' => $appointment->id], 201)
            : back()->with('success', 'Appointment request received.');
    }

    public function slots(Request $request)
    {
        $validated = $request->validate(['date' => ['required', 'date', 'after_or_equal:today']]);
        $date = now()->parse($validated['date'])->toDateString();
        $doctor = User::whereIn('role', ['doctor', 'admin'])->first();
        $slots = $doctor
            ? DoctorAvailability::where('doctor_id', $doctor->id)->where('is_available', true)->whereDate('starts_at', $date)->orderBy('starts_at')->get()
                ->filter(fn (DoctorAvailability $availability) => ! Appointment::where('doctor_id', $doctor->id)->where('starts_at', $availability->starts_at)->whereNotIn('status', ['cancelled', 'rejected'])->exists())
                ->map(fn (DoctorAvailability $availability) => ['value' => $availability->starts_at->toDateTimeString(), 'label' => $availability->starts_at->format('g:i A')])->values()
            : collect();

        return response()->json(['data' => $slots]);
    }
}
