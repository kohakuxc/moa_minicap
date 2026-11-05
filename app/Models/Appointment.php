<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $fillable = ['user_id', 'doctor_id', 'service', 'specialist', 'starts_at', 'notes', 'reason', 'status'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function appointmentNotes()
    {
        return $this->hasMany(AppointmentNote::class);
    }
}
