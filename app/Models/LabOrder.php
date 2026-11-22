<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabOrder extends Model
{
    public const STATUS_ORDERED = 'ordered';
    public const STATUS_IN_EDGING_COATING = 'in_edging_coating';
    public const STATUS_QUALITY_CHECK = 'quality_check';
    public const STATUS_READY_FOR_PICKUP = 'ready_for_pickup';
    public const STATUS_DISPENSED = 'dispensed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_number',
        'patient_id',
        'prescription_id',
        'frame_id',
        'lens_catalog_id',
        'optician_id',
        'lab_vendor',
        'status',
        'estimated_completion_date',
        'completed_at',
        'lab_notes',
    ];

    protected function casts(): array
    {
        return [
            'estimated_completion_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function frame(): BelongsTo
    {
        return $this->belongsTo(Frame::class);
    }

    public function lensCatalog(): BelongsTo
    {
        return $this->belongsTo(LensCatalog::class);
    }

    public function optician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'optician_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
