<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\ClinicService;
use App\Models\DoctorAvailability;
use App\Models\DoctorLeaveBlock;
use App\Models\Frame;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LabOrder;
use App\Models\Prescription;
use App\Models\QueueTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $today = now()->toDateString();

        // 5. Low-Stock Inventory Alerts
        $lowStockFrames = Frame::where('is_active', true)
            ->whereColumn('stock_quantity', '<=', 'reorder_threshold')
            ->orderBy('stock_quantity')
            ->get();

        // Cached KPIs (30 seconds) with consolidated SQL to minimize remote roundtrips
        $kpis = Cache::remember('admin.dashboard.kpis.' . $today, 30, function () use ($today) {
            $apptStats = DB::selectOne("
                SELECT
                    COUNT(*) FILTER (WHERE starts_at::date = CURRENT_DATE) as today_appointments,
                    COUNT(*) FILTER (WHERE starts_at::date = CURRENT_DATE AND status = 'completed') as today_completed,
                    COUNT(*) FILTER (WHERE starts_at < NOW()) as total_past,
                    COUNT(*) FILTER (WHERE starts_at < NOW() AND status IN ('cancelled', 'rejected', 'no_show')) as total_no_shows
                FROM appointments
            ");

            $revStats = DB::selectOne("
                SELECT
                    COALESCE(SUM(CASE WHEN item_type = 'service' THEN line_total ELSE 0 END), 0) as consultation_revenue,
                    COALESCE(SUM(CASE WHEN item_type IN ('frame', 'lens', 'accessory', 'contact_lens') THEN line_total ELSE 0 END), 0) as retail_revenue
                FROM invoice_items
            ");

            $totalPast = (int) ($apptStats->total_past ?? 0);
            $totalNoShows = (int) ($apptStats->total_no_shows ?? 0);
            $noShowRate = $totalPast > 0 ? round(($totalNoShows / $totalPast) * 100, 1) : 0.0;
            $consultationRevenue = (float) ($revStats->consultation_revenue ?? 0);
            $retailRevenue = (float) ($revStats->retail_revenue ?? 0);

            $pendingLabOrders = LabOrder::whereIn('status', [
                LabOrder::STATUS_ORDERED,
                LabOrder::STATUS_IN_EDGING_COATING,
                LabOrder::STATUS_QUALITY_CHECK,
            ])->count();

            $activeQueueCount = QueueTicket::whereDate('check_in_time', $today)
                ->whereIn('status', [QueueTicket::STATUS_WAITING, QueueTicket::STATUS_IN_CONSULTATION, QueueTicket::STATUS_DISPENSING])
                ->count();

            return [
                'today_appointments' => (int) ($apptStats->today_appointments ?? 0),
                'today_completed' => (int) ($apptStats->today_completed ?? 0),
                'no_show_rate' => $noShowRate,
                'total_past' => $totalPast,
                'consultation_revenue' => $consultationRevenue,
                'retail_revenue' => $retailRevenue,
                'total_revenue' => $consultationRevenue + $retailRevenue,
                'pending_lab_orders' => $pendingLabOrders,
                'active_queue_count' => $activeQueueCount,
            ];
        });

        $kpis['low_stock_count'] = $lowStockFrames->count();

        // Recent Audit Logs for Transparency
        $recentAuditLogs = AuditLog::with('actor')
            ->latest('created_at')
            ->take(15)
            ->get();

        return view('dashboard.admin', [
            'patients' => User::where('role', User::ROLE_PATIENT)->latest()->paginate(10, ['*'], 'patients'),
            'appointments' => Appointment::with(['user', 'doctor', 'clinicService'])->latest('starts_at')->paginate(10, ['*'], 'appointments'),
            'availability' => $request->user()->availabilities()->where('starts_at', '>=', now())->orderBy('starts_at')->get(),
            'prescriptions' => Prescription::with(['user', 'doctor'])->latest('issued_at')->paginate(10, ['*'], 'prescriptions'),
            'kpis' => $kpis,
            'lowStockFrames' => $lowStockFrames,
            'recentAuditLogs' => $recentAuditLogs,
        ]);
    }

    public function updateAppointment(Request $request, Appointment $appointment)
    {
        Gate::authorize('update', $appointment);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['requested', 'confirmed', 'in_progress', 'completed', 'rejected', 'cancelled', 'rescheduled', 'no_show'])],
            'starts_at' => ['nullable', 'date'],
            'exam_room' => ['nullable', 'string', 'max:60'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if (in_array($validated['status'], ['rejected', 'cancelled', 'rescheduled'], true) && blank($validated['reason'])) {
            return back()->withErrors(['reason' => 'A reason is required for this status change.']);
        }

        $appointment->update(array_filter([
            'status' => $validated['status'],
            'starts_at' => $validated['starts_at'] ?? null,
            'exam_room' => $validated['exam_room'] ?? null,
            'reason' => $validated['reason'] ?? null,
        ], fn ($value) => $value !== null));

        AuditLog::record($request->user(), 'appointment.status_updated:'.$validated['status'], $appointment);

        return back()->with('success', 'Appointment updated successfully.');
    }

    public function storeAvailability(Request $request)
    {
        $validated = $request->validate([
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);

        abort_if(now()->parse($validated['starts_at'])->isWeekend(), 422, 'Doctor availability must be on a weekday.');

        $slot = $request->user()->availabilities()->create($validated);
        AuditLog::record($request->user(), 'doctor.availability_added', $slot);

        return back()->with('success', 'Doctor availability published.');
    }

    public function storePrescription(Request $request, User $user)
    {
        Gate::authorize('create', Prescription::class);

        $validated = $request->validate([
            'eye' => ['required', 'in:OD,OS'],
            'rx_type' => ['nullable', 'string', 'in:spectacle,contact_lens'],
            'sphere' => ['nullable', 'numeric', 'between:-30,30'],
            'cylinder' => ['nullable', 'numeric', 'between:-30,30'],
            'axis' => ['nullable', 'integer', 'between:0,180'],
            'add' => ['nullable', 'numeric', 'between:0,10'],
            'pd' => ['nullable', 'numeric', 'between:20,100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'issued_at' => ['required', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
        ]);

        $validated['doctor_id'] = $request->user()->id;
        $validated['signature_token'] = hash('sha256', $user->id . $request->user()->id . now()->toIso8601String() . ($validated['sphere'] ?? '0'));
        $validated['is_verified'] = true;

        $prescription = $user->prescriptions()->create($validated);
        AuditLog::record($request->user(), 'prescription.created', $prescription);

        return back()->with('success', 'Prescription signed and recorded.');
    }
}
