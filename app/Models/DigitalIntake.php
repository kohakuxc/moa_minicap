<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalIntake extends Model
{
    protected $fillable = [
        'appointment_id',
        'patient_id',
        'medical_history',
        'symptoms',
        'ocular_history',
        'current_medications',
        'allergies',
        'hmo_provider_name',
        'hmo_member_id',
        'preferred_eyewear',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
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
}
