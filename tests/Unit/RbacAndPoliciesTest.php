<?php

namespace Tests\Unit;

use App\Models\Appointment;
use App\Models\AuditLog;
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
use Tests\TestCase;

class RbacAndPoliciesTest extends TestCase
{
    protected User $admin;
    protected User $optometrist;
    protected User $optician;
    protected User $receptionist;
    protected User $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = new User(['name' => 'Admin User', 'role' => User::ROLE_ADMIN]);
        $this->admin->id = 1;

        $this->optometrist = new User(['name' => 'Dr. Vance', 'role' => User::ROLE_OPTOMETRIST]);
        $this->optometrist->id = 2;

        $this->optician = new User(['name' => 'Optician Marcus', 'role' => User::ROLE_OPTICIAN]);
        $this->optician->id = 3;

        $this->receptionist = new User(['name' => 'Receptionist Sarah', 'role' => User::ROLE_RECEPTIONIST]);
        $this->receptionist->id = 4;

        $this->patient = new User(['name' => 'Patient Jane', 'role' => User::ROLE_PATIENT]);
        $this->patient->id = 5;
    }

    public function test_user_role_helper_methods(): void
    {
        // Admin
        $this->assertTrue($this->admin->isAdmin());
        $this->assertTrue($this->admin->isStaff());
        $this->assertTrue($this->admin->isOptometrist());
        $this->assertTrue($this->admin->isOptician());
        $this->assertTrue($this->admin->isReceptionist());
        $this->assertFalse($this->admin->isPatient());

        // Optometrist
        $this->assertFalse($this->optometrist->isAdmin());
        $this->assertTrue($this->optometrist->isStaff());
        $this->assertTrue($this->optometrist->isOptometrist());
        $this->assertTrue($this->optometrist->isDoctor());
        $this->assertFalse($this->optometrist->isOptician());
        $this->assertFalse($this->optometrist->isReceptionist());
        $this->assertFalse($this->optometrist->isPatient());

        // Optician
        $this->assertFalse($this->optician->isAdmin());
        $this->assertTrue($this->optician->isStaff());
        $this->assertFalse($this->optician->isOptometrist());
        $this->assertTrue($this->optician->isOptician());
        $this->assertFalse($this->optician->isReceptionist());
        $this->assertFalse($this->optician->isPatient());

        // Receptionist
        $this->assertFalse($this->receptionist->isAdmin());
        $this->assertTrue($this->receptionist->isStaff());
        $this->assertFalse($this->receptionist->isOptometrist());
        $this->assertFalse($this->receptionist->isOptician());
        $this->assertTrue($this->receptionist->isReceptionist());
        $this->assertFalse($this->receptionist->isPatient());

        // Patient
        $this->assertFalse($this->patient->isAdmin());
        $this->assertFalse($this->patient->isStaff());
        $this->assertFalse($this->patient->isOptometrist());
        $this->assertFalse($this->patient->isOptician());
        $this->assertFalse($this->patient->isReceptionist());
        // Original Admin Identification
        $originalAdmin = new User(['name' => 'Original Admin', 'email' => 'doctor.demo@moa.local', 'role' => User::ROLE_ADMIN]);
        $secondaryAdmin = new User(['name' => 'Secondary Admin', 'email' => 'other.admin@moa.test', 'role' => User::ROLE_ADMIN]);

        $this->assertTrue($originalAdmin->isOriginalAdmin());
        $this->assertFalse($secondaryAdmin->isOriginalAdmin());
        $this->assertFalse($this->optometrist->isOriginalAdmin());
    }

    public function test_gates_evaluation(): void
    {
        $this->assertTrue(Gate::forUser($this->admin)->allows('admin'));
        $this->assertTrue(Gate::forUser($this->admin)->allows('staff'));

        $this->assertTrue(Gate::forUser($this->optometrist)->allows('optometrist'));
        $this->assertTrue(Gate::forUser($this->optometrist)->allows('doctor'));
        $this->assertTrue(Gate::forUser($this->optometrist)->allows('staff'));
        $this->assertFalse(Gate::forUser($this->optometrist)->allows('admin'));

        $this->assertTrue(Gate::forUser($this->optician)->allows('optician'));
        $this->assertTrue(Gate::forUser($this->optician)->allows('staff'));
        $this->assertFalse(Gate::forUser($this->optician)->allows('optometrist'));

        $this->assertTrue(Gate::forUser($this->receptionist)->allows('receptionist'));
        $this->assertTrue(Gate::forUser($this->receptionist)->allows('staff'));

        $this->assertFalse(Gate::forUser($this->patient)->allows('staff'));
        $this->assertTrue(Gate::forUser($this->patient)->allows('patient'));
    }

    public function test_clinical_examination_policy(): void
    {
        $policy = new ClinicalExaminationPolicy();
        $exam = new ClinicalExamination();
        $exam->patient_id = 5;
        $exam->optometrist_id = 2;

        $this->assertTrue($policy->viewAny($this->optometrist));
        $this->assertTrue($policy->create($this->optometrist));
        $this->assertTrue($policy->update($this->optometrist, $exam));

        $this->assertFalse($policy->create($this->optician));
        $this->assertFalse($policy->create($this->receptionist));
        $this->assertFalse($policy->create($this->patient));

        $this->assertTrue($policy->view($this->patient, $exam));
    }

    public function test_inventory_and_lab_order_policies(): void
    {
        $invPolicy = new InventoryPolicy();
        $this->assertTrue($invPolicy->manage($this->optician));
        $this->assertTrue($invPolicy->manage($this->admin));
        $this->assertFalse($invPolicy->manage($this->optometrist));
        $this->assertFalse($invPolicy->manage($this->receptionist));

        $labPolicy = new LabOrderPolicy();
        $order = new LabOrder();
        $order->patient_id = 5;

        $this->assertTrue($labPolicy->create($this->optician));
        $this->assertTrue($labPolicy->update($this->optician, $order));
        $this->assertFalse($labPolicy->create($this->receptionist));
        $this->assertTrue($labPolicy->view($this->patient, $order));
    }

    public function test_queue_and_billing_policies(): void
    {
        $queuePolicy = new QueuePolicy();
        $this->assertTrue($queuePolicy->manage($this->receptionist));
        $this->assertTrue($queuePolicy->manage($this->admin));
        $this->assertFalse($queuePolicy->manage($this->patient));

        $billingPolicy = new BillingPolicy();
        $this->assertTrue($billingPolicy->create($this->receptionist));
        $this->assertTrue($billingPolicy->create($this->optician));
        $this->assertFalse($billingPolicy->approveRefund($this->receptionist));
        $this->assertTrue($billingPolicy->approveRefund($this->admin));
    }
}
