<?php

namespace App\Policies;

use App\Models\User;

class InventoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function manage(User $user): bool
    {
        return $user->isOptician() || $user->isAdmin();
    }
}
