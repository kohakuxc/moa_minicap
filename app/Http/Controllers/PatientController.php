<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\ClinicService;
use App\Models\Frame;
use App\Models\HmoProvider;
use App\Models\LabOrder;
use App\Models\Prescription;
use App\Models\TryOnReservation;
use App\Models\User;
use App\Support\BookingRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PatientController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        if ($user->isStaff()) {
            return redirect()->route('admin.dashboard');
        }

        if (! $user->profile_completed_at) {
            return redirect()->route('profile.create');
        }

        // Active Lab Orders for Eyewear Tracker
        $activeLabOrders = LabOrder::with(['frame', 'lensCatalog', 'prescription'])
            ->where('patient_id', $user->id)
            ->whereIn('status', [
                LabOrder::STATUS_ORDERED,
                LabOrder::STATUS_IN_EDGING_COATING,
                LabOrder::STATUS_QUALITY_CHECK,
                LabOrder::STATUS_READY_FOR_PICKUP,
            ])
            ->latest('created_at')
            ->get();

        $activeReservations = TryOnReservation::with('frame')
            ->where('patient_id', $user->id)
            ->where('status', 'reserved')
            ->where('reserved_until', '>=', now())
            ->get();

        $appointments = $user->appointments()->orderBy('starts_at')->get();
        $upcoming = $appointments->filter(fn($a) => $a->starts_at >= now() && !in_array($a->status, ['cancelled', 'rejected']));

        return view('patient.dashboard', [
            'user' => $user,
            'upcomingCount' => $upcoming->count(),
            'prescriptionCount' => $user->prescriptions()->count(),
            'visitCount' => $appointments->filter(fn($a) => $a->starts_at < now())->count(),
            'nextAppointment' => $upcoming->first(),
            'activeLabOrders' => $activeLabOrders,
            'activeReservations' => $activeReservations,
        ]);
    }

    public function createAppointment(Request $request)
    {
        $services = Cache::remember('clinic_services.active', 60, fn () => ClinicService::where('is_active', true)->orderBy('name')->get());
        $doctors = Cache::remember('doctors.active', 60, fn () => User::whereIn('role', [User::ROLE_OPTOMETRIST, User::ROLE_DOCTOR, User::ROLE_ADMIN])->get());
        $hmoProviders = Cache::remember('hmo_providers.active', 60, fn () => HmoProvider::where('is_active', true)->orderBy('name')->get());

        return view('patient.appointments.create', [
            'user' => $request->user(),
            'services' => $services,
            'doctors' => $doctors,
            'hmoProviders' => $hmoProviders,
            'activeAppointment' => BookingRules::activeAppointmentFor($request->user()),
        ]);
    }

    public function eyewear(Request $request)
    {
        $query = Frame::where('is_active', true);

        if ($shape = $request->query('shape')) {
            $query->where('shape', $shape);
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($material = $request->query('material')) {
            $query->where('material', $material);
        }

        if ($gender = $request->query('gender')) {
            $query->where('gender', $gender);
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('brand', 'ILIKE', "%{$search}%")
                  ->orWhere('model_name', 'ILIKE', "%{$search}%")
                  ->orWhere('color', 'ILIKE', "%{$search}%");
            });
        }

        $frames = $query->orderBy('stock_quantity', 'desc')->paginate(9)->withQueryString();

        $userReservations = $request->user()
            ? $request->user()->tryOnReservations()->where('status', 'reserved')->pluck('frame_id')->toArray()
            : [];

        return view('patient.eyewear', [
            'user' => $request->user(),
            'frames' => $frames,
            'userReservations' => $userReservations,
            'filters' => $request->only(['shape', 'category', 'material', 'gender', 'q']),
        ]);
    }

    public function reserveTryOn(Request $request, Frame $frame)
    {
        $user = $request->user();

        // Check if already reserved
        $existing = TryOnReservation::where('patient_id', $user->id)
            ->where('frame_id', $frame->id)
            ->where('status', 'reserved')
            ->where('reserved_until', '>=', now())
            ->first();

        if ($existing) {
            return back()->with('info', "You already have '{$frame->brand} {$frame->model_name}' reserved until " . $existing->reserved_until->format('M d, g:i A') . '.');
        }

        $reservation = TryOnReservation::create([
            'patient_id' => $user->id,
            'frame_id' => $frame->id,
            'status' => 'reserved',
            'reserved_until' => now()->addHours(48),
            'notes' => 'Reserved via online eyewear showcase for in-store fitting.',
        ]);

        AuditLog::record($user, 'try_on_reservation.created', $reservation);

        return back()->with('success', "Frame '{$frame->brand} {$frame->model_name}' reserved for in-store try-on! Held for 48 hours until " . $reservation->reserved_until->format('M d, g:i A') . '.');
    }

    public function careServices(Request $request)
    {
        $services = Cache::remember('clinic_services.active', 60, fn () => ClinicService::where('is_active', true)->orderBy('name')->get());

        return view('patient.care-services', [
            'user' => $request->user(),
            'services' => $services,
        ]);
    }

    public function upcomingAppointments(Request $request)
    {
        return view('patient.appointments.upcoming', [
            'user' => $request->user(),
            'appointments' => $request->user()->appointments()->where('starts_at', '>=', now())->orderBy('starts_at')->get(),
        ]);
    }

    public function prescriptions(Request $request)
    {
        return view('patient.prescriptions', [
            'user' => $request->user(),
            'prescriptions' => $request->user()->prescriptions()->latest('issued_at')->get(),
        ]);
    }

    public function printPrescription(Request $request, Prescription $prescription)
    {
        Gate::authorize('view', $prescription);

        // Record sensitive patient health view in audit log
        AuditLog::record($request->user(), 'prescription.viewed_or_printed', $prescription);

        // Also fetch counterpart eye if exists from same date/examination
        $counterpart = Prescription::where('user_id', $prescription->user_id)
            ->where('id', '!=', $prescription->id)
            ->where('issued_at', $prescription->issued_at)
            ->first();

        $odPrescription = $prescription->eye === 'OD' ? $prescription : $counterpart;
        $osPrescription = $prescription->eye === 'OS' ? $prescription : $counterpart;

        return view('patient.prescriptions.print', [
            'user' => $request->user(),
            'patient' => $prescription->user,
            'prescription' => $prescription,
            'odPrescription' => $odPrescription,
            'osPrescription' => $osPrescription,
        ]);
    }

    public function visitHistory(Request $request)
    {
        $appointments = $request->user()->appointments()
            ->with(['clinicService', 'invoices.items'])
            ->where('starts_at', '<', now())
            ->latest('starts_at')
            ->get();

        return view('patient.appointments.history', [
            'user' => $request->user(),
            'appointments' => $appointments,
        ]);
    }

    public function createProfile(Request $request)
    {
        if ($request->user()->profile_completed_at) {
            return redirect()->route('dashboard');
        }

        return view('profile.create', ['user' => $request->user(), 'editing' => false]);
    }

    public function profile(Request $request)
    {
        return view('profile.create', ['user' => $request->user(), 'editing' => true]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $wasCompleted = (bool) $user->profile_completed_at;
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users')->ignore($user)],
        ]);
        if ($validated['email'] !== $user->email) {
            $user->forceFill(['email' => $validated['email'], 'email_verified_at' => null]);
        }
        $user->fill($validated)->forceFill(['profile_completed_at' => now()])->save();

        return redirect()
            ->route($wasCompleted ? 'patient.profile' : 'dashboard')
            ->with('success', 'Profile saved.');
    }

    public function cancelAppointment(Request $request, Appointment $appointment)
    {
        Gate::authorize('delete', $appointment);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:'.BookingRules::CANCELLATION_REASON_MIN, 'max:500'],
        ], [
            'reason.required' => 'Please tell us why you are cancelling this appointment.',
            'reason.min' => 'Please give a little more detail about why you are cancelling (at least '.BookingRules::CANCELLATION_REASON_MIN.' characters).',
        ]);

        if (! in_array($appointment->status, BookingRules::ACTIVE_STATUSES, true)) {
            return back()->withErrors(['reason' => 'Only active appointments can be cancelled.']);
        }

        $appointment->update([
            'status' => 'cancelled',
            'reason' => trim($validated['reason']),
        ]);

        AuditLog::record($request->user(), 'appointment.cancelled_by_patient', $appointment);

        return back()->with('success', 'Appointment cancelled.');
    }
}
