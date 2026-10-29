<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        return view('dashboard.admin', [
            'patients' => User::where('role', 'patient')->latest()->paginate(15),
            'appointments' => Appointment::with('user')->latest('starts_at')->paginate(15, ['*'], 'appointments'),
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
        $this->authorize('update', $appointment);
        $validated = $request->validate(['status' => ['required', 'in:requested,confirmed,completed,cancelled']]);
        $appointment->update($validated);
        AuditLog::create([
            'actor_id' => $request->user()->id,
            'action' => 'appointment.status_updated',
            'subject_type' => Appointment::class,
            'subject_id' => $appointment->id,
        ]);

        return back()->with('success', 'Appointment status updated.');
    }

    public function storePrescription(Request $request, User $user)
    {
        $this->authorize('create', Prescription::class);
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
