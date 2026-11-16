<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\PatientController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', fn () => redirect('/'))->name('login');
Route::get('/register', [AuthController::class, 'showRegister'])->middleware('guest')->name('register');

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
    ->middleware('guest')
    ->name('password.email');
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->middleware('guest')->name('password.request');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->middleware('guest')->name('password.reset');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])
    ->middleware('guest')
    ->name('password.update');
Route::get('/email/verify', fn () => view('auth.verify-email'))
    ->middleware('auth')->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route($request->user()->profile_completed_at ? 'dashboard' : 'profile.create')
        ->with('success', $request->user()->profile_completed_at
            ? 'Email verified.'
            : 'Email verified. Please complete your profile.');
})->middleware(['auth', 'signed', 'throttle:6,1'])->name('verification.verify');
Route::post('/email/verification-notification', [AuthController::class, 'sendVerification'])
    ->middleware(['auth', 'throttle:6,1'])->name('verification.send');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/profile/create', [PatientController::class, 'createProfile'])->name('profile.create');
    Route::get('/dashboard', [PatientController::class, 'dashboard'])->name('dashboard');
    Route::get('/appointments/create', [PatientController::class, 'createAppointment'])->name('patient.appointments.create');
    Route::get('/eyewear', [PatientController::class, 'eyewear'])->name('patient.eyewear');
    Route::get('/care-services', [PatientController::class, 'careServices'])->name('patient.care-services');
    Route::get('/appointments/upcoming', [PatientController::class, 'upcomingAppointments'])->name('patient.appointments.upcoming');
    Route::get('/prescriptions', [PatientController::class, 'prescriptions'])->name('patient.prescriptions');
    Route::get('/appointments/history', [PatientController::class, 'visitHistory'])->name('patient.appointments.history');
    Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::get('/appointment-slots', [AppointmentController::class, 'slots'])->name('appointments.slots');
    Route::patch('/profile', [PatientController::class, 'updateProfile'])->name('profile.update');
    Route::delete('/appointments/{appointment}', [PatientController::class, 'cancelAppointment'])->name('appointments.cancel');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');
    Route::patch('/appointments/{appointment}', [AdminController::class, 'updateAppointment'])->name('appointments.update');
    Route::post('/availability', [AdminController::class, 'storeAvailability'])->name('availability.store');
    Route::post('/patients/{user}/prescriptions', [AdminController::class, 'storePrescription'])->name('prescriptions.store');
});
