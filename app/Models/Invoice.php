<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invoice extends Model
{
    public const STATUS_UNPAID = 'unpaid';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_PAID = 'paid';
    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'invoice_number',
        'patient_id',
        'appointment_id',
        'lab_order_id',
        'hmo_provider_id',
        'subtotal',
        'discount_amount',
        'hmo_covered_amount',
        'patient_payable_amount',
        'total_paid',
        'payment_method',
        'payment_status',
        'refund_reason',
        'refund_approved_by',
        'created_by',
        'reconciled_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'hmo_covered_amount' => 'decimal:2',
            'patient_payable_amount' => 'decimal:2',
            'total_paid' => 'decimal:2',
            'reconciled_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function labOrder(): BelongsTo
    {
        return $this->belongsTo(LabOrder::class);
    }

    public function hmoProvider(): BelongsTo
    {
        return $this->belongsTo(HmoProvider::class);
    }

    public function refundApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refund_approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function hmoClaim(): HasOne
    {
        return $this->hasOne(HmoClaim::class);
    }
}
