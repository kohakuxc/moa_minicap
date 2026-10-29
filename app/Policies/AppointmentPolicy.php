<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function view(User $user, Appointment $appointment): bool
    {
        return $user->isAdmin() || $appointment->user_id === $user->id;
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $appointment->user_id === $user->id && $appointment->starts_at->isFuture();
    }
}
