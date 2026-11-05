<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Prescription;
use App\Models\User;
use App\Models\DoctorAvailability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        return view('dashboard.admin', [
            'patients' => User::where('role', 'patient')->latest()->paginate(15),
            'appointments' => Appointment::with(['user', 'doctor'])->latest('starts_at')->paginate(15, ['*'], 'appointments'),
            'availability' => $request->user()->availabilities()->where('starts_at', '>=', now())->orderBy('starts_at')->get(),
            'prescriptions' => Prescription::with('user')->latest('issued_at')->paginate(15, ['*'], 'prescriptions'),
            'counts' => [
                'patients' => User::where('role', 'patient')->count(),
                'appointments' => Appointment::count(),
                'prescriptions' => Prescription::count(),
            ],
        ]);
    }

    public function updateAppointment(Request $request, Appointment $appointment)
    {
        Gate::authorize('update', $appointment);
        $validated = $request->validate([
            'status' => ['required', Rule::in(['requested', 'confirmed', 'in_progress', 'completed', 'rejected', 'cancelled', 'rescheduled'])],
            'starts_at' => ['nullable', 'date', 'after:now'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);
        if (in_array($validated['status'], ['rejected', 'cancelled', 'rescheduled'], true) && blank($validated['reason'])) {
            return back()->withErrors(['reason' => 'A reason is required for this status.']);
        }
        $appointment->update(array_filter([
            'status' => $validated['status'],
            'starts_at' => $validated['starts_at'] ?? null,
            'reason' => $validated['reason'] ?? null,
        ], fn ($value) => $value !== null));
        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'appointment.status_updated',
            'subject_type' => Appointment::class,
            'subject_id' => $appointment->id,
        ]);

        return back()->with('success', 'Appointment status updated.');
    }

    public function storeAvailability(Request $request)
    {
        $validated = $request->validate([
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);
        abort_if(now()->parse($validated['starts_at'])->isWeekend(), 422, 'Availability must be on a weekday.');
        $request->user()->availabilities()->create($validated);

        return back()->with('success', 'Availability added.');
    }

    public function storePrescription(Request $request, User $user)
    {
        Gate::authorize('create', Prescription::class);
        $validated = $request->validate([
            'eye' => ['required', 'in:OD,OS'],
            'sphere' => ['nullable', 'numeric', 'between:-30,30'],
            'cylinder' => ['nullable', 'numeric', 'between:-30,30'],
            'axis' => ['nullable', 'integer', 'between:0,180'],
            'pd' => ['nullable', 'numeric', 'between:20,100'],
            'issued_at' => ['required', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
        ]);
        $prescription = $user->prescriptions()->create($validated);
        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'prescription.created',
            'subject_type' => Prescription::class,
            'subject_id' => $prescription->id,
        ]);

        return back()->with('success', 'Prescription added.');
    }
}
