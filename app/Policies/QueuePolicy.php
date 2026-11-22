<?php

namespace App\Policies;

use App\Models\QueueTicket;
use App\Models\User;

class QueuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function manage(User $user): bool
    {
        return $user->isReceptionist() || $user->isAdmin();
    }
}
