<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;

class PatientController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        if ($user->isDoctor()) {
            return redirect()->route('admin.dashboard');
        }

        if (! $user->profile_completed_at) {
            return redirect()->route('profile.create');
        }

        return view('dashboard.patient', [
            'user' => $user,
            'upcomingAppointments' => $user->appointments()->where('starts_at', '>=', now())->orderBy('starts_at')->get(),
            'pastAppointments' => $user->appointments()->where('starts_at', '<', now())->latest('starts_at')->limit(10)->get(),
            'prescriptions' => $user->prescriptions()->latest('issued_at')->get(),
        ]);
    }

    public function createProfile(Request $request)
    {
        return view('profile.create', ['user' => $request->user()]);
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
        $user->fill($validated)->forceFill(['profile_completed_at' => now()])->save();

        return redirect()->route('dashboard')->with('success', 'Profile saved.');
    }

    public function cancelAppointment(Request $request, int $appointment)
    {
        $record = $request->user()->appointments()->findOrFail($appointment);
        Gate::authorize('delete', $record);
        $validated = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        abort_if($record->starts_at->isPast() || in_array($record->status, ['cancelled', 'rejected'], true), 422);
        $record->update(['status' => 'cancelled', 'reason' => $validated['reason']]);

        return back()->with('success', 'Appointment cancelled.');
    }
}
