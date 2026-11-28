<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\ClinicService;
use App\Models\QueueTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class QueueController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', QueueTicket::class);

        $today = now()->toDateString();

        // Today's tickets separated by status
        $waitingTickets = QueueTicket::with(['appointment', 'patient', 'clinicService'])
            ->whereDate('check_in_time', $today)
            ->where('status', QueueTicket::STATUS_WAITING)
            ->orderBy('check_in_time')
            ->get();

        $inConsultationTickets = QueueTicket::with(['appointment', 'patient', 'clinicService'])
            ->whereDate('check_in_time', $today)
            ->where('status', QueueTicket::STATUS_IN_CONSULTATION)
            ->orderBy('called_at', 'desc')
            ->get();

        $dispensingTickets = QueueTicket::with(['appointment', 'patient', 'clinicService'])
            ->whereDate('check_in_time', $today)
            ->where('status', QueueTicket::STATUS_DISPENSING)
            ->orderBy('updated_at', 'desc')
            ->get();

        $completedTickets = QueueTicket::with(['appointment', 'patient', 'clinicService'])
            ->whereDate('check_in_time', $today)
            ->whereIn('status', [QueueTicket::STATUS_COMPLETED, QueueTicket::STATUS_NO_SHOW])
            ->latest('completed_at')
            ->take(15)
            ->get();

        // Today's appointments available for check-in
        $todayAppointments = Appointment::with(['user', 'clinicService', 'queueTicket'])
            ->whereDate('starts_at', $today)
            ->whereNotIn('status', ['cancelled', 'rejected', 'no_show', 'completed'])
            ->orderBy('starts_at')
            ->get();

        $services = ClinicService::where('is_active', true)->orderBy('name')->get();
        $patients = User::where('role', User::ROLE_PATIENT)
            ->whereNotNull('email_verified_at')
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        return view('admin.queue', [
            'waitingTickets' => $waitingTickets,
            'inConsultationTickets' => $inConsultationTickets,
            'dispensingTickets' => $dispensingTickets,
            'completedTickets' => $completedTickets,
            'todayAppointments' => $todayAppointments,
            'services' => $services,
            'patients' => $patients,
        ]);
    }

    public function generateWalkIn(Request $request)
    {
        Gate::authorize('manage', QueueTicket::class);

        $validated = $request->validate([
            'patient_name' => ['required', 'string', 'max:150'],
            'patient_id' => ['nullable', 'exists:users,id'],
            'service_id' => ['nullable', 'exists:clinic_services,id'],
            'assigned_room' => ['nullable', 'string', 'max:60'],
        ]);

        // Generate sequential walk-in token for today e.g. W-101
        $todayCount = QueueTicket::whereDate('check_in_time', now()->toDateString())
            ->where('ticket_number', 'LIKE', 'W-%')
            ->count() + 1;
        $ticketNumber = sprintf('W-%03d', $todayCount);

        $ticket = QueueTicket::create([
            'ticket_number' => $ticketNumber,
            'patient_id' => $validated['patient_id'] ?? null,
            'patient_name' => $validated['patient_name'],
            'service_id' => $validated['service_id'] ?? null,
            'assigned_room' => $validated['assigned_room'] ?? null,
            'status' => QueueTicket::STATUS_WAITING,
            'check_in_time' => now(),
        ]);

        AuditLog::record($request->user(), 'queue_ticket.walk_in_generated', $ticket);

        return back()->with('success', "Walk-in ticket {$ticket->ticket_number} created for {$ticket->patient_name}.");
    }

    public function checkInAppointment(Request $request, Appointment $appointment)
    {
        Gate::authorize('manage', QueueTicket::class);

        // Check if ticket already exists for this appointment
        if ($appointment->queueTicket) {
            return back()->with('info', "Appointment already checked in as ticket {$appointment->queueTicket->ticket_number}.");
        }

        $todayCount = QueueTicket::whereDate('check_in_time', now()->toDateString())
            ->where('ticket_number', 'LIKE', 'A-%')
            ->count() + 1;
        $ticketNumber = sprintf('A-%03d', $todayCount);

        $examRoom = $request->input('exam_room', $appointment->exam_room ?? 'Exam Room 1');

        $ticket = QueueTicket::create([
            'ticket_number' => $ticketNumber,
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->user_id,
            'patient_name' => $appointment->user->name,
            'service_id' => $appointment->service_id,
            'assigned_room' => $examRoom,
            'status' => QueueTicket::STATUS_WAITING,
            'check_in_time' => now(),
        ]);

        $appointment->update([
            'status' => 'in_progress',
            'checked_in_at' => now(),
            'check_in_token' => $ticketNumber,
            'exam_room' => $examRoom,
        ]);

        AuditLog::record($request->user(), 'appointment.checked_in', $appointment);

        return back()->with('success', "Appointment checked in: Ticket {$ticketNumber} issued for {$appointment->user->name}.");
    }

    public function updateTicketStatus(Request $request, QueueTicket $ticket)
    {
        Gate::authorize('manage', QueueTicket::class);

        $validated = $request->validate([
            'status' => ['required', Rule::in([
                QueueTicket::STATUS_WAITING,
                QueueTicket::STATUS_IN_CONSULTATION,
                QueueTicket::STATUS_DISPENSING,
                QueueTicket::STATUS_COMPLETED,
                QueueTicket::STATUS_NO_SHOW,
                QueueTicket::STATUS_CANCELLED,
            ])],
            'assigned_room' => ['nullable', 'string', 'max:60'],
        ]);

        $updates = ['status' => $validated['status']];
        if (! empty($validated['assigned_room'])) {
            $updates['assigned_room'] = $validated['assigned_room'];
        }

        if ($validated['status'] === QueueTicket::STATUS_IN_CONSULTATION && ! $ticket->called_at) {
            $updates['called_at'] = now();
        }

        if (in_array($validated['status'], [QueueTicket::STATUS_COMPLETED, QueueTicket::STATUS_NO_SHOW], true)) {
            $updates['completed_at'] = now();
            if ($ticket->appointment_id) {
                $aptStatus = $validated['status'] === QueueTicket::STATUS_NO_SHOW ? 'no_show' : 'completed';
                $ticket->appointment?->update(['status' => $aptStatus]);
            }
        }

        $ticket->update($updates);
        AuditLog::record($request->user(), 'queue_ticket.status_updated:'.$validated['status'], $ticket);

        return back()->with('success', "Ticket {$ticket->ticket_number} moved to " . ucfirst(str_replace('_', ' ', $validated['status'])) . ".");
    }

    public function liveBoard()
    {
        $today = now()->toDateString();
        $inConsultation = QueueTicket::whereDate('check_in_time', $today)
            ->where('status', QueueTicket::STATUS_IN_CONSULTATION)
            ->orderBy('called_at', 'desc')
            ->get(['ticket_number', 'patient_name', 'assigned_room', 'called_at']);

        $waiting = QueueTicket::whereDate('check_in_time', $today)
            ->where('status', QueueTicket::STATUS_WAITING)
            ->orderBy('check_in_time')
            ->take(10)
            ->get(['ticket_number', 'patient_name', 'check_in_time']);

        $dispensing = QueueTicket::whereDate('check_in_time', $today)
            ->where('status', QueueTicket::STATUS_DISPENSING)
            ->orderBy('updated_at', 'desc')
            ->take(6)
            ->get(['ticket_number', 'patient_name']);

        return view('admin.live-queue', [
            'inConsultation' => $inConsultation,
            'waiting' => $waiting,
            'dispensing' => $dispensing,
        ]);
    }
}
