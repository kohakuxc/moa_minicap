<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecallAlert extends Model
{
    public const TYPE_ANNUAL_EXAM = 'annual_exam';
    public const TYPE_CONTACT_LENS_REFILL = 'contact_lens_refill';
    public const TYPE_FOLLOW_UP = 'follow_up';

    public const STATUS_PENDING = 'pending';
    public const STATUS_NOTIFIED = 'notified';
    public const STATUS_BOOKED = 'booked';
    public const STATUS_DISMISSED = 'dismissed';

    protected $fillable = [
        'patient_id',
        'recall_type',
        'due_date',
        'status',
        'last_notified_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'last_notified_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }
}
