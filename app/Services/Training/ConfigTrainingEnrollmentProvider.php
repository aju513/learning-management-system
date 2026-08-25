<?php

namespace App\Services\Training;

use App\Models\User;
use Illuminate\Support\Arr;

class ConfigTrainingEnrollmentProvider implements TrainingEnrollmentProviderInterface
{
    public function isEnrolled(User $user, string $trainingKey): bool
    {
        $enrolledTrainingKeys = config('training.enrollments.'.((string) $user->getKey()), []);

        return in_array($trainingKey, Arr::wrap($enrolledTrainingKeys), true);
    }
}
