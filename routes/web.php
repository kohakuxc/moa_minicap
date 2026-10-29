<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

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

Route::post('/auth/register', [AuthController::class, 'register'])
    ->middleware('guest')
    ->name('auth.register');
Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('guest')
    ->name('auth.login');
Route::post('/auth/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('auth.logout');
