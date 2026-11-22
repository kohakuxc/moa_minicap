<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Prescription;
use App\Models\RecallAlert;
use App\Models\User;
use Illuminate\Console\Command;

class SendRecallsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clinic:send-recalls {--dry-run : Only inspect and count without creating alerts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flag and dispatch automated recall reminders for annual exams and contact lens refills';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $this->info('Starting MOA Optical Clinic automated recall scan...');

        $annualCount = 0;
        $refillCount = 0;

        // 1. Annual Eye Exam Recalls: Check patients with completed appointments or prescriptions from >= 11 months ago
        $patients = User::where('role', User::ROLE_PATIENT)->get();

        foreach ($patients as $patient) {
            $lastExam = Appointment::where('user_id', $patient->id)
                ->where('status', 'completed')
                ->latest('starts_at')
                ->first();

            $lastRx = Prescription::where('user_id', $patient->id)
                ->latest('issued_at')
                ->first();

            $lastVisitDate = null;
            if ($lastExam && $lastRx) {
                $lastVisitDate = $lastExam->starts_at->max($lastRx->issued_at);
            } elseif ($lastExam) {
                $lastVisitDate = $lastExam->starts_at;
            } elseif ($lastRx) {
                $lastVisitDate = $lastRx->issued_at;
            }

            if ($lastVisitDate && $lastVisitDate->lte(now()->subMonths(11))) {
                $existingPending = RecallAlert::where('patient_id', $patient->id)
                    ->where('recall_type', RecallAlert::TYPE_ANNUAL_EXAM)
                    ->whereIn('status', [RecallAlert::STATUS_PENDING, RecallAlert::STATUS_NOTIFIED])
                    ->where('created_at', '>=', now()->subMonths(3))
                    ->exists();

                if (! $existingPending) {
                    $annualCount++;
                    if (! $isDryRun) {
                        $alert = RecallAlert::create([
                            'patient_id' => $patient->id,
                            'recall_type' => RecallAlert::TYPE_ANNUAL_EXAM,
                            'due_date' => $lastVisitDate->copy()->addYear()->toDateString(),
                            'status' => RecallAlert::STATUS_NOTIFIED,
                            'last_notified_at' => now(),
                            'notes' => 'Automated annual vision health check recall notification sent.',
                        ]);

                        AuditLog::record(null, 'recall.annual_exam_notified', $alert);
                    }
                }
            }

            // 2. Contact Lens Refill Recalls: Check active contact lens prescriptions expiring within 30 days
            $contactLensRx = Prescription::where('user_id', $patient->id)
                ->where('rx_type', 'contact_lens')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now()->addDays(30))
                ->latest('expires_at')
                ->first();

            if ($contactLensRx) {
                $existingRefillAlert = RecallAlert::where('patient_id', $patient->id)
                    ->where('recall_type', RecallAlert::TYPE_CONTACT_LENS_REFILL)
                    ->whereIn('status', [RecallAlert::STATUS_PENDING, RecallAlert::STATUS_NOTIFIED])
                    ->where('created_at', '>=', now()->subDays(60))
                    ->exists();

                if (! $existingRefillAlert) {
                    $refillCount++;
                    if (! $isDryRun) {
                        $alert = RecallAlert::create([
                            'patient_id' => $patient->id,
                            'recall_type' => RecallAlert::TYPE_CONTACT_LENS_REFILL,
                            'due_date' => $contactLensRx->expires_at->toDateString(),
                            'status' => RecallAlert::STATUS_NOTIFIED,
                            'last_notified_at' => now(),
                            'notes' => 'Automated contact lens renewal recall notification sent.',
                        ]);

                        AuditLog::record(null, 'recall.contact_lens_notified', $alert);
                    }
                }
            }
        }

        $this->info("Recall Scan Complete: {$annualCount} annual exam recalls, {$refillCount} contact lens refill alerts generated" . ($isDryRun ? ' (dry-run mode).' : '.'));

        return self::SUCCESS;
    }
}
