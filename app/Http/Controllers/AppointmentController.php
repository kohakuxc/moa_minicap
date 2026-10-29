<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'service' => ['required', 'string', 'max:100'],
            'specialist' => ['nullable', 'string', 'max:120'],
            'starts_at' => ['required', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $appointment = $request->user()->appointments()->create($validated);

        return response()->json(['message' => 'Appointment request received.', 'id' => $appointment->id], 201);
    }
}
