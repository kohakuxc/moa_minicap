<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HmoClaim extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'claim_number',
        'invoice_id',
        'hmo_provider_id',
        'patient_id',
        'member_id',
        'approval_code',
        'claimed_amount',
        'approved_amount',
        'status',
        'rejection_reason',
        'submitted_at',
        'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'claimed_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'submitted_at' => 'datetime',
            'settled_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function hmoProvider(): BelongsTo
    {
        return $this->belongsTo(HmoProvider::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }
}
