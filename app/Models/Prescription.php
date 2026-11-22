<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends Model
{
    protected $fillable = [
        'user_id',
        'examination_id',
        'doctor_id',
        'eye',
        'rx_type',
        'sphere',
        'cylinder',
        'axis',
        'pd',
        'add',
        'notes',
        'signature_token',
        'is_verified',
        'issued_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'sphere' => 'decimal:2',
            'cylinder' => 'decimal:2',
            'pd' => 'decimal:2',
            'add' => 'decimal:2',
            'is_verified' => 'boolean',
            'issued_at' => 'date',
            'expires_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function examination(): BelongsTo
    {
        return $this->belongsTo(ClinicalExamination::class, 'examination_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function labOrders(): HasMany
    {
        return $this->hasMany(LabOrder::class);
    }
}
