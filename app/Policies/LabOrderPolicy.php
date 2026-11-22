<?php

namespace App\Policies;

use App\Models\LabOrder;
use App\Models\User;

class LabOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, LabOrder $order): bool
    {
        return $user->isStaff() || $order->patient_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isOptician() || $user->isAdmin();
    }

    public function update(User $user, LabOrder $order): bool
    {
        return $user->isOptician() || $user->isAdmin();
    }
}
