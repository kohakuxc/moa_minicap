<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PatientController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

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
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])
    ->middleware('throttle:5,1')
    ->name('password.email');
Route::get('/forgot-password', fn () => view('auth.forgot-password'))->name('password.request');
Route::get('/reset-password/{token}', fn (string $token, Request $request) => view('auth.reset-password', [
    'token' => $token,
    'email' => $request->query('email'),
]))->name('password.reset');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])
    ->middleware('throttle:5,1')
    ->name('password.update');
Route::get('/email/verify', fn () => view('auth.verify-email'))
    ->middleware('auth')->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect('/dashboard')->with('success', 'Email verified.');
})->middleware(['auth', 'signed', 'throttle:6,1'])->name('verification.verify');
Route::post('/email/verification-notification', [AuthController::class, 'sendVerification'])
    ->middleware(['auth', 'throttle:6,1'])->name('verification.send');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/dashboard', [PatientController::class, 'dashboard'])->name('dashboard');
    Route::patch('/profile', [PatientController::class, 'updateProfile'])->name('profile.update');
    Route::delete('/appointments/{appointment}', [PatientController::class, 'cancelAppointment'])->name('appointments.cancel');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');
    Route::patch('/appointments/{appointment}', [AdminController::class, 'updateAppointment'])->name('appointments.update');
    Route::post('/patients/{user}/prescriptions', [AdminController::class, 'storePrescription'])->name('prescriptions.store');
});
