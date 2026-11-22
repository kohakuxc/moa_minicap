<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueTicket extends Model
{
    public const STATUS_WAITING = 'waiting';
    public const STATUS_IN_CONSULTATION = 'in_consultation';
    public const STATUS_DISPENSING = 'dispensing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_NO_SHOW = 'no_show';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'ticket_number',
        'appointment_id',
        'patient_id',
        'patient_name',
        'service_id',
        'assigned_room',
        'status',
        'check_in_time',
        'called_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'check_in_time' => 'datetime',
            'called_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function clinicService(): BelongsTo
    {
        return $this->belongsTo(ClinicService::class, 'service_id');
    }
}
