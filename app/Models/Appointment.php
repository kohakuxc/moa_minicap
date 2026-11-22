<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    protected $fillable = [
        'user_id',
        'doctor_id',
        'service_id',
        'service',
        'specialist',
        'exam_room',
        'starts_at',
        'duration_minutes',
        'notes',
        'reason',
        'status',
        'checked_in_at',
        'check_in_token',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'duration_minutes' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function clinicService(): BelongsTo
    {
        return $this->belongsTo(ClinicService::class, 'service_id');
    }

    public function appointmentNotes(): HasMany
    {
        return $this->hasMany(AppointmentNote::class);
    }

    public function digitalIntake(): HasOne
    {
        return $this->hasOne(DigitalIntake::class);
    }

    public function clinicalExamination(): HasOne
    {
        return $this->hasOne(ClinicalExamination::class);
    }

    public function queueTicket(): HasOne
    {
        return $this->hasOne(QueueTicket::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
