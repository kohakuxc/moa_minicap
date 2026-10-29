<?php

namespace App\Policies;

use App\Models\Prescription;
use App\Models\User;

class PrescriptionPolicy
{
    public function view(User $user, Prescription $prescription): bool
    {
        return $user->isAdmin() || $prescription->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }
}
