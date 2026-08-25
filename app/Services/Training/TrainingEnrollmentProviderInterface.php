<?php

namespace App\Services\Training;

use App\Models\User;

interface TrainingEnrollmentProviderInterface
{
    public function isEnrolled(User $user, string $trainingKey): bool;
}
