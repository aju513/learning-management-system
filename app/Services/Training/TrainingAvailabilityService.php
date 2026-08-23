<?php

namespace App\Services\Training;

use App\Enums\AvailabilityScope;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class TrainingAvailabilityService
{
    public function __construct(
        private readonly TrainingCatalogProviderInterface $catalog,
        private readonly TrainingEnrollmentProviderInterface $enrollments,
    ) {}

    /** @return array<int, string> */
    public function eligibleTrainingKeys(User $user): array
    {
        return $this->catalog->all()
            ->filter(fn (array $training): bool => $this->enrollments->isEnrolled($user, $training['key']))
            ->pluck('key')
            ->values()
            ->all();
    }

    public function isAvailable(Course|Assessment $content, User $user): bool
    {
        if ($this->scopeValue($content) !== AvailabilityScope::Training->value) {
            return true;
        }

        $trainingKey = (string) $content->required_training_key;

        return filled($trainingKey) && $this->enrollments->isEnrolled($user, $trainingKey);
    }

    public function assertAvailable(Course|Assessment $content, User $user): void
    {
        if (! $this->isAvailable($content, $user)) {
            throw new AuthorizationException('This content requires enrollment in the assigned training first.');
        }
    }

    private function scopeValue(Course|Assessment $content): string
    {
        return $content->availability_scope instanceof AvailabilityScope
            ? $content->availability_scope->value
            : (string) ($content->availability_scope ?: AvailabilityScope::All->value);
    }
}
