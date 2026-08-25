<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\AssessmentRepositoryInterface;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Services\Training\TrainingAvailabilityService;
use App\Services\Training\TrainingCatalogProviderInterface;

class TraineeOverviewService
{
    public function __construct(
        private readonly CourseRepositoryInterface $courses,
        private readonly AssessmentRepositoryInterface $assessments,
        private readonly TrainingAvailabilityService $availability,
        private readonly TrainingCatalogProviderInterface $trainingCatalog,
        private readonly CreditScoreService $credits,
    ) {}

    public function for(User $trainee): array
    {
        $eligibleTrainingKeys = $this->availability->eligibleTrainingKeys($trainee);
        $tests = $this->assessments->availableFor($trainee, $eligibleTrainingKeys)
            ->filter(fn ($assessment): bool => $assessment->isAvailable())
            ->values();

        return [
            'context' => 'Courses and tests available to you',
            'availableCourses' => $this->courses->availableForOverview($trainee, $eligibleTrainingKeys),
            'availableTests' => $tests,
            'trainingNames' => $this->trainingCatalog->all()->pluck('name', 'key')->all(),
            'creditAlerts' => $this->credits->dashboardData($trainee),
        ];
    }
}
