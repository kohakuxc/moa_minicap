<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/appointments', function (\Illuminate\Http\Request $request) {
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:120'],
        'email' => ['required', 'email'],
        'phone' => ['required', 'string', 'max:30'],
    ]);

    return response()->json(['message' => 'Appointment request received', 'appointment' => $validated], 201);
})->name('appointments.store');
