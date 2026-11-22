<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class BillingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->isStaff() || $invoice->patient_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function approveRefund(User $user): bool
    {
        return $user->isAdmin();
    }
}
