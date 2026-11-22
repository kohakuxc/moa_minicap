<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicService extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'category',
        'duration_minutes',
        'fee',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'fee' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'service_id');
    }

    public function queueTickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class, 'service_id');
    }
}
