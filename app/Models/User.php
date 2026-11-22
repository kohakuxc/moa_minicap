<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_OPTOMETRIST = 'optometrist';
    public const ROLE_DOCTOR = 'doctor';
    public const ROLE_OPTICIAN = 'optician';
    public const ROLE_RECEPTIONIST = 'receptionist';
    public const ROLE_PATIENT = 'patient';

    public const ORIGINAL_ADMIN_EMAIL = 'doctor.demo@moa.local';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'password',
        'profile_completed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function doctorAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(DoctorAvailability::class, 'doctor_id');
    }

    public function leaveBlocks(): HasMany
    {
        return $this->hasMany(DoctorLeaveBlock::class, 'doctor_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function doctorPrescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'doctor_id');
    }

    public function clinicalExaminations(): HasMany
    {
        return $this->hasMany(ClinicalExamination::class, 'patient_id');
    }

    public function performedExaminations(): HasMany
    {
        return $this->hasMany(ClinicalExamination::class, 'optometrist_id');
    }

    public function digitalIntakes(): HasMany
    {
        return $this->hasMany(DigitalIntake::class, 'patient_id');
    }

    public function labOrders(): HasMany
    {
        return $this->hasMany(LabOrder::class, 'patient_id');
    }

    public function assignedLabOrders(): HasMany
    {
        return $this->hasMany(LabOrder::class, 'optician_id');
    }

    public function tryOnReservations(): HasMany
    {
        return $this->hasMany(TryOnReservation::class, 'patient_id');
    }

    public function queueTickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class, 'patient_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'patient_id');
    }

    public function createdInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'created_by');
    }

    public function hmoClaims(): HasMany
    {
        return $this->hasMany(HmoClaim::class, 'patient_id');
    }

    public function recallAlerts(): HasMany
    {
        return $this->hasMany(RecallAlert::class, 'patient_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isOriginalAdmin(): bool
    {
        return strtolower(trim($this->email)) === self::ORIGINAL_ADMIN_EMAIL;
    }

    public function isOptometrist(): bool
    {
        return in_array($this->role, [self::ROLE_OPTOMETRIST, self::ROLE_DOCTOR, self::ROLE_ADMIN], true);
    }

    public function isDoctor(): bool
    {
        return $this->isOptometrist();
    }

    public function isOptician(): bool
    {
        return in_array($this->role, [self::ROLE_OPTICIAN, self::ROLE_ADMIN], true);
    }

    public function isReceptionist(): bool
    {
        return in_array($this->role, [self::ROLE_RECEPTIONIST, self::ROLE_ADMIN], true);
    }

    public function isStaff(): bool
    {
        return in_array($this->role, [
            self::ROLE_ADMIN,
            self::ROLE_OPTOMETRIST,
            self::ROLE_DOCTOR,
            self::ROLE_OPTICIAN,
            self::ROLE_RECEPTIONIST,
        ], true);
    }

    public function isPatient(): bool
    {
        return $this->role === self::ROLE_PATIENT;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'profile_completed_at' => 'datetime',
        ];
    }
}
