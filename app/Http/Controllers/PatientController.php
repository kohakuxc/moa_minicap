<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PatientController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();

        return view('dashboard.patient', [
            'user' => $user,
            'upcomingAppointments' => $user->appointments()->where('starts_at', '>=', now())->orderBy('starts_at')->get(),
            'pastAppointments' => $user->appointments()->where('starts_at', '<', now())->latest('starts_at')->limit(10)->get(),
            'prescriptions' => $user->prescriptions()->latest('issued_at')->get(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users')->ignore($user)],
        ]);
        if ($validated['email'] !== $user->email) {
            $user->forceFill(['email' => $validated['email'], 'email_verified_at' => null]);
        }
        $user->fill($validated)->save();

        return back()->with('success', 'Profile updated.');
    }

    public function cancelAppointment(Request $request, int $appointment)
    {
        $record = $request->user()->appointments()->findOrFail($appointment);
        abort_if($record->starts_at->isPast() || $record->status === 'cancelled', 422);
        $record->update(['status' => 'cancelled']);

        return back()->with('success', 'Appointment cancelled.');
    }
}
