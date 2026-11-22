<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicalExamination extends Model
{
    protected $fillable = [
        'patient_id',
        'optometrist_id',
        'appointment_id',
        'chief_complaint',
        'visual_acuity_od',
        'visual_acuity_os',
        'sph_od',
        'cyl_od',
        'axis_od',
        'add_od',
        'sph_os',
        'cyl_os',
        'axis_os',
        'add_os',
        'pd',
        'iop_od',
        'iop_os',
        'slit_lamp_notes',
        'fundus_notes',
        'diagnosis',
        'management_plan',
        'digital_signature_token',
        'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'sph_od' => 'decimal:2',
            'cyl_od' => 'decimal:2',
            'axis_od' => 'integer',
            'add_od' => 'decimal:2',
            'sph_os' => 'decimal:2',
            'cyl_os' => 'decimal:2',
            'axis_os' => 'integer',
            'add_os' => 'decimal:2',
            'pd' => 'decimal:2',
            'iop_od' => 'decimal:2',
            'iop_os' => 'decimal:2',
            'signed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function optometrist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'optometrist_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'examination_id');
    }
}
