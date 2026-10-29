<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prescription extends Model
{
    protected $fillable = ['eye', 'sphere', 'cylinder', 'axis', 'pd', 'issued_at', 'expires_at'];

    protected function casts(): array
    {
        return ['issued_at' => 'date', 'expires_at' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
