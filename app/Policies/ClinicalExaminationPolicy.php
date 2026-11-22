<?php

namespace App\Policies;

use App\Models\ClinicalExamination;
use App\Models\User;

class ClinicalExaminationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, ClinicalExamination $exam): bool
    {
        return $user->isStaff() || $exam->patient_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isOptometrist();
    }

    public function update(User $user, ClinicalExamination $exam): bool
    {
        return $user->isOptometrist();
    }
}
