<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ClinicalController;
use App\Http\Controllers\DispensingController;
use App\Http\Controllers\LabOrderController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\SchedulingController;
use App\Http\Controllers\StaffManagementController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', fn () => redirect('/'))->name('login');
Route::get('/register', [AuthController::class, 'showRegister'])->middleware('guest')->name('register');

Route::post('/auth/register', [AuthController::class, 'register'])
    ->middleware('guest')
    ->name('auth.register');
Route::get('/register/verify', [AuthController::class, 'showPendingVerification'])
    ->middleware('guest')
    ->name('registration.verify.notice');
Route::get('/register/verify-email/{token}/{emailToken}', [AuthController::class, 'verifyPendingEmail'])
    ->middleware(['guest', 'signed', 'throttle:10,1'])
    ->name('registration.verify-email');
Route::post('/register/verify-otp', [AuthController::class, 'verifyPendingOtp'])
    ->middleware(['guest', 'throttle:10,1'])
    ->name('registration.verify-otp');
Route::post('/register/resend-otp', [AuthController::class, 'resendPendingOtp'])
    ->middleware(['guest', 'throttle:6,1'])
    ->name('registration.resend-otp');
Route::post('/register/resend-email', [AuthController::class, 'resendPendingEmail'])
    ->middleware(['guest', 'throttle:6,1'])
    ->name('registration.resend-email');
Route::post('/register/cancel', [AuthController::class, 'cancelPendingRegistration'])
    ->middleware('guest')
    ->name('registration.cancel');
Route::get('/register/status', [AuthController::class, 'registrationStatus'])
    ->middleware('throttle:60,1')
    ->name('registration.status');
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
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
Route::post('/email/verification-notification', [AuthController::class, 'sendVerification'])
    ->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// Authenticated Patient Portal Routes
Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/profile/create', [PatientController::class, 'createProfile'])->name('profile.create');
    Route::get('/profile', [PatientController::class, 'profile'])->name('patient.profile');
    Route::get('/dashboard', [PatientController::class, 'dashboard'])->name('dashboard');
    Route::get('/appointments/create', [PatientController::class, 'createAppointment'])->name('patient.appointments.create');
    Route::get('/eyewear', [PatientController::class, 'eyewear'])->name('patient.eyewear');
    Route::post('/eyewear/reserve/{frame}', [PatientController::class, 'reserveTryOn'])->name('patient.eyewear.reserve');
    Route::get('/care-services', [PatientController::class, 'careServices'])->name('patient.care-services');
    Route::get('/appointments/upcoming', [PatientController::class, 'upcomingAppointments'])->name('patient.appointments.upcoming');
    Route::get('/prescriptions', [PatientController::class, 'prescriptions'])->name('patient.prescriptions');
    Route::get('/prescriptions/{prescription}/print', [PatientController::class, 'printPrescription'])->name('patient.prescriptions.print');
    Route::get('/appointments/history', [PatientController::class, 'visitHistory'])->name('patient.appointments.history');
    Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::get('/appointment-slots', [AppointmentController::class, 'slots'])->name('appointments.slots');
    Route::patch('/profile', [PatientController::class, 'updateProfile'])->name('profile.update');
    Route::delete('/appointments/{appointment}', [PatientController::class, 'cancelAppointment'])->name('appointments.cancel');
});

// Admin & Staff Internal Clinic Routes
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    // 1. Executive Dashboard & Analytics
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');
    Route::patch('/appointments/{appointment}', [AdminController::class, 'updateAppointment'])->name('appointments.update');
    Route::post('/availability', [AdminController::class, 'storeAvailability'])->name('availability.store');
    Route::post('/patients/{user}/prescriptions', [AdminController::class, 'storePrescription'])->name('prescriptions.store');

    // 2. Master Scheduling & Clinic Configuration
    Route::get('/scheduling', [SchedulingController::class, 'index'])->name('scheduling');
    Route::post('/scheduling/leave', [SchedulingController::class, 'storeLeaveBlock'])->name('leave.store');
    Route::delete('/scheduling/leave/{leaveBlock}', [SchedulingController::class, 'destroyLeaveBlock'])->name('leave.destroy');
    Route::post('/scheduling/services', [SchedulingController::class, 'storeService'])->name('services.store');
    Route::patch('/scheduling/services/{service}/toggle', [SchedulingController::class, 'toggleService'])->name('services.toggle');

    // 3. Clinical EHR & Digital Prescription Builder (Optometrist)
    Route::get('/clinical', [ClinicalController::class, 'index'])->name('clinical');
    Route::post('/clinical/examinations', [ClinicalController::class, 'storeExamination'])->name('examinations.store');

    // 4. Front-Desk & Queue Management (Receptionist)
    Route::get('/queue', [QueueController::class, 'index'])->name('queue');
    Route::post('/queue/walk-in', [QueueController::class, 'generateWalkIn'])->name('queue.walk-in');
    Route::post('/queue/check-in/{appointment}', [QueueController::class, 'checkInAppointment'])->name('queue.check-in');
    Route::patch('/queue/ticket/{ticket}', [QueueController::class, 'updateTicketStatus'])->name('queue.ticket.update');
    Route::get('/live-queue', [QueueController::class, 'liveBoard'])->name('live-queue');

    // 5. Optical Dispensing & POS (Optician)
    Route::get('/dispensing', [DispensingController::class, 'index'])->name('dispensing');
    Route::post('/dispensing/frames', [DispensingController::class, 'storeFrame'])->name('dispensing.frames.store');
    Route::patch('/dispensing/frames/{frame}/adjust', [DispensingController::class, 'adjustStock'])->name('dispensing.frames.adjust');
    Route::post('/dispensing/checkout', [DispensingController::class, 'checkout'])->name('dispensing.checkout');

    // 6. Optical Lab Vendor Pipeline
    Route::get('/lab-orders', [LabOrderController::class, 'index'])->name('lab-orders');
    Route::patch('/lab-orders/{labOrder}/status', [LabOrderController::class, 'updateStatus'])->name('lab-orders.status');

    // 7. Financial & HMO Governance
    Route::get('/billing', [BillingController::class, 'index'])->name('billing');
    Route::post('/billing/hmo', [BillingController::class, 'storeHmoProvider'])->name('billing.hmo.store');
    Route::patch('/billing/claims/{claim}/status', [BillingController::class, 'updateClaimStatus'])->name('billing.claims.status');
    Route::post('/billing/reconcile', [BillingController::class, 'reconcileDaily'])->name('billing.reconcile');
    Route::post('/billing/refund/{invoice}', [BillingController::class, 'approveRefund'])->name('billing.refund');

    // 8. Automated Recall CRM Trigger
    Route::post('/recalls/trigger', function (Request $request) {
        Artisan::call('clinic:send-recalls');
        return back()->with('success', 'Automated recall scan executed. Patients due for annual exams or contact lens refills have been notified.');
    })->name('recalls.trigger');

    // 9. Staff & Administrator Governance
    Route::get('/staff', [StaffManagementController::class, 'index'])->name('staff.index');
    Route::post('/staff', [StaffManagementController::class, 'store'])->name('staff.store');
    Route::patch('/staff/{user}', [StaffManagementController::class, 'update'])->name('staff.update');
    Route::patch('/staff/{user}/revoke', [StaffManagementController::class, 'revoke'])->name('staff.revoke');

    // 10. Administrator Self-Profile & Security
    Route::get('/profile', [StaffManagementController::class, 'profile'])->name('profile');
    Route::patch('/profile', [StaffManagementController::class, 'updateProfile'])->name('profile.update');
});
