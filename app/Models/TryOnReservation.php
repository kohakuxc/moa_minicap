<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TryOnReservation extends Model
{
    protected $fillable = [
        'patient_id',
        'frame_id',
        'status',
        'reserved_until',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'reserved_until' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function frame(): BelongsTo
    {
        return $this->belongsTo(Frame::class);
    }
}
