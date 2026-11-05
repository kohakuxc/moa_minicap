<?php

namespace App\Http\Controllers;

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
            'specialist' => ['nullable', 'string', 'max:120'],
            'starts_at' => ['required', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $startsAt = now()->parse($validated['starts_at']);
        $endsAt = $startsAt->copy()->addMinutes(60);

        $appointment = DB::transaction(function () use ($request, $validated, $startsAt, $endsAt) {
            if (DB::connection()->getDriverName() === 'pgsql') {
                DB::select('select pg_advisory_xact_lock(hashtext(?))', [
                    $startsAt->format('Y-m-d H:i'),
                ]);
            }

            $conflict = DB::table('appointments')
                ->whereIn('status', ['requested', 'confirmed'])
                ->where('starts_at', '<', $endsAt)
                ->where('starts_at', '>=', $startsAt->copy()->subMinutes(60))
                ->lockForUpdate()
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'starts_at' => 'That time is already reserved. Please choose another time.',
                ]);
            }

            return $request->user()->appointments()->create($validated);
        });

        return response()->json(['message' => 'Appointment request received.', 'id' => $appointment->id], 201);
    }
}
