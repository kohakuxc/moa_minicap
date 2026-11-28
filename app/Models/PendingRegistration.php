<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingRegistration extends Model
{
    protected $fillable = [
        'token',
        'name',
        'email',
        'canonical_email',
        'phone',
        'date_of_birth',
        'password',
        'email_token_hash',
        'email_verified_at',
        'phone_otp_hash',
        'phone_otp_expires_at',
        'phone_otp_attempts',
        'phone_verified_at',
        'last_sms_sent_at',
        'last_email_sent_at',
        'expires_at',
    ];

    protected $hidden = [
        'password',
        'email_token_hash',
        'phone_otp_hash',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'email_verified_at' => 'datetime',
            'phone_otp_expires_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_sms_sent_at' => 'datetime',
            'last_email_sent_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function hasVerifiedPhone(): bool
    {
        return $this->phone_verified_at !== null;
    }

    public function isFullyVerified(): bool
    {
        return $this->hasVerifiedEmail() && $this->hasVerifiedPhone();
    }

    public function isPhoneOtpExpired(): bool
    {
        return $this->phone_otp_expires_at->isPast();
    }
}
