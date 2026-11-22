<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\ClinicalExamination;
use App\Models\Invoice;
use App\Models\LabOrder;
use App\Models\Prescription;
use App\Models\QueueTicket;
use App\Models\User;
use App\Policies\AppointmentPolicy;
use App\Policies\BillingPolicy;
use App\Policies\ClinicalExaminationPolicy;
use App\Policies\InventoryPolicy;
use App\Policies\LabOrderPolicy;
use App\Policies\PrescriptionPolicy;
use App\Policies\QueuePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Role Gates
        Gate::define('admin', fn (User $user): bool => $user->isAdmin());
        Gate::define('doctor', fn (User $user): bool => $user->isDoctor());
        Gate::define('optometrist', fn (User $user): bool => $user->isOptometrist());
        Gate::define('optician', fn (User $user): bool => $user->isOptician());
        Gate::define('receptionist', fn (User $user): bool => $user->isReceptionist());
        Gate::define('staff', fn (User $user): bool => $user->isStaff());
        Gate::define('patient', fn (User $user): bool => $user->isPatient());

        // Explicit Policy Registrations
        Gate::policy(Appointment::class, AppointmentPolicy::class);
        Gate::policy(Prescription::class, PrescriptionPolicy::class);
        Gate::policy(ClinicalExamination::class, ClinicalExaminationPolicy::class);
        Gate::policy(LabOrder::class, LabOrderPolicy::class);
        Gate::policy(QueueTicket::class, QueuePolicy::class);
        Gate::policy(Invoice::class, BillingPolicy::class);
    }
}
