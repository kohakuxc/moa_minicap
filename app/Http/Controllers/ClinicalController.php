<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\ClinicalExamination;
use App\Models\Prescription;
use App\Models\QueueTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ClinicalController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('create', ClinicalExamination::class);

        $selectedPatientId = $request->query('patient_id');
        $selectedPatient = $selectedPatientId ? User::find($selectedPatientId) : null;

        $selectedAppointmentId = $request->query('appointment_id');
        $selectedAppointment = $selectedAppointmentId ? Appointment::with(['user', 'digitalIntake'])->find($selectedAppointmentId) : null;

        if ($selectedAppointment && ! $selectedPatient) {
            $selectedPatient = $selectedAppointment->user;
        }

        // Active waiting or in-consultation patients from queue
        $queuePatients = QueueTicket::with(['appointment', 'patient'])
            ->whereDate('check_in_time', now()->toDateString())
            ->whereIn('status', [QueueTicket::STATUS_WAITING, QueueTicket::STATUS_IN_CONSULTATION])
            ->orderBy('check_in_time')
            ->get();

        $allPatients = User::where('role', User::ROLE_PATIENT)->orderBy('name')->get(['id', 'name', 'email', 'phone']);

        $recentExaminations = ClinicalExamination::with(['patient', 'optometrist', 'prescriptions'])
            ->latest('created_at')
            ->paginate(10);

        return view('admin.clinical', [
            'selectedPatient' => $selectedPatient,
            'selectedAppointment' => $selectedAppointment,
            'queuePatients' => $queuePatients,
            'allPatients' => $allPatients,
            'recentExaminations' => $recentExaminations,
        ]);
    }

    public function storeExamination(Request $request)
    {
        Gate::authorize('create', ClinicalExamination::class);

        $validated = $request->validate([
            'patient_id' => ['required', 'exists:users,id'],
            'appointment_id' => ['nullable', 'exists:appointments,id'],
            'chief_complaint' => ['nullable', 'string', 'max:2000'],
            'visual_acuity_od' => ['nullable', 'string', 'max:50'],
            'visual_acuity_os' => ['nullable', 'string', 'max:50'],
            'sph_od' => ['nullable', 'numeric', 'between:-30,30'],
            'cyl_od' => ['nullable', 'numeric', 'between:-30,30'],
            'axis_od' => ['nullable', 'integer', 'between:0,180'],
            'add_od' => ['nullable', 'numeric', 'between:0,10'],
            'sph_os' => ['nullable', 'numeric', 'between:-30,30'],
            'cyl_os' => ['nullable', 'numeric', 'between:-30,30'],
            'axis_os' => ['nullable', 'integer', 'between:0,180'],
            'add_os' => ['nullable', 'numeric', 'between:0,10'],
            'pd' => ['nullable', 'numeric', 'between:20,100'],
            'iop_od' => ['nullable', 'numeric', 'between:0,80'],
            'iop_os' => ['nullable', 'numeric', 'between:0,80'],
            'slit_lamp_notes' => ['nullable', 'string', 'max:2000'],
            'fundus_notes' => ['nullable', 'string', 'max:2000'],
            'diagnosis' => ['nullable', 'string', 'max:1000'],
            'management_plan' => ['nullable', 'string', 'max:2000'],
            'issue_prescription' => ['nullable', 'boolean'],
            'rx_type' => ['nullable', 'string', 'in:spectacle,contact_lens'],
        ]);

        $optometrist = $request->user();
        $patient = User::findOrFail($validated['patient_id']);

        // Generate tamper-evident cryptographic digital signature token
        $signaturePayload = implode('|', [
            $patient->id,
            $optometrist->id,
            $validated['sph_od'] ?? '0',
            $validated['cyl_od'] ?? '0',
            $validated['sph_os'] ?? '0',
            $validated['cyl_os'] ?? '0',
            now()->timestamp,
            config('app.key'),
        ]);
        $digitalSignature = hash('sha256', $signaturePayload);

        $examData = array_merge($validated, [
            'optometrist_id' => $optometrist->id,
            'digital_signature_token' => $digitalSignature,
            'signed_at' => now(),
        ]);
        unset($examData['issue_prescription'], $examData['rx_type']);

        $exam = DB::transaction(function () use ($examData, $validated, $patient, $optometrist, $digitalSignature) {
            $createdExam = ClinicalExamination::create($examData);

            // Audit log the clinical intake
            AuditLog::record($optometrist, 'clinical_examination.signed_and_recorded', $createdExam);

            // Issue separate OD and OS prescription records if requested
            if (! empty($validated['issue_prescription'])) {
                $rxType = $validated['rx_type'] ?? 'spectacle';
                $validityYears = $rxType === 'contact_lens' ? 1 : 2;

                // OD Prescription
                if (! is_null($validated['sph_od']) || ! is_null($validated['cyl_od'])) {
                    $rxOd = Prescription::create([
                        'user_id' => $patient->id,
                        'examination_id' => $createdExam->id,
                        'doctor_id' => $optometrist->id,
                        'eye' => 'OD',
                        'rx_type' => $rxType,
                        'sphere' => $validated['sph_od'],
                        'cylinder' => $validated['cyl_od'],
                        'axis' => $validated['axis_od'],
                        'pd' => $validated['pd'],
                        'add' => $validated['add_od'],
                        'notes' => 'Right eye prescription from examination #' . $createdExam->id,
                        'signature_token' => $digitalSignature,
                        'is_verified' => true,
                        'issued_at' => now()->toDateString(),
                        'expires_at' => now()->addYears($validityYears)->toDateString(),
                    ]);
                    AuditLog::record($optometrist, 'prescription.od_issued', $rxOd);
                }

                // OS Prescription
                if (! is_null($validated['sph_os']) || ! is_null($validated['cyl_os'])) {
                    $rxOs = Prescription::create([
                        'user_id' => $patient->id,
                        'examination_id' => $createdExam->id,
                        'doctor_id' => $optometrist->id,
                        'eye' => 'OS',
                        'rx_type' => $rxType,
                        'sphere' => $validated['sph_os'],
                        'cylinder' => $validated['cyl_os'],
                        'axis' => $validated['axis_os'],
                        'pd' => $validated['pd'],
                        'add' => $validated['add_os'],
                        'notes' => 'Left eye prescription from examination #' . $createdExam->id,
                        'signature_token' => $digitalSignature,
                        'is_verified' => true,
                        'issued_at' => now()->toDateString(),
                        'expires_at' => now()->addYears($validityYears)->toDateString(),
                    ]);
                    AuditLog::record($optometrist, 'prescription.os_issued', $rxOs);
                }
            }

            // Update linked appointment to completed
            if (! empty($validated['appointment_id'])) {
                $apt = Appointment::find($validated['appointment_id']);
                if ($apt) {
                    $apt->update(['status' => 'completed']);
                    AuditLog::record($optometrist, 'appointment.status_updated:completed', $apt);
                }
            }

            // Update associated queue ticket to dispensing or completed
            $queueTicket = QueueTicket::where('patient_id', $patient->id)
                ->whereIn('status', [QueueTicket::STATUS_WAITING, QueueTicket::STATUS_IN_CONSULTATION])
                ->latest('check_in_time')
                ->first();

            if ($queueTicket) {
                $nextStatus = ! empty($validated['issue_prescription']) ? QueueTicket::STATUS_DISPENSING : QueueTicket::STATUS_COMPLETED;
                $queueTicket->update([
                    'status' => $nextStatus,
                    'completed_at' => now(),
                ]);
                AuditLog::record($optometrist, 'queue_ticket.status_updated:'.$nextStatus, $queueTicket);
            }

            return $createdExam;
        });

        return redirect()->route('admin.clinical')
            ->with('success', "Clinical examination #{$exam->id} recorded and digitally signed for {$patient->name}.");
    }
}
