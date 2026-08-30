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
        private readonly LearningService $learning,
    ) {}

    public function for(User $trainee): array
    {
        $eligibleTrainingKeys = $this->availability->eligibleTrainingKeys($trainee);
        $tests = $this->assessments->availableFor($trainee, $eligibleTrainingKeys)
            ->filter(fn ($assessment): bool => $assessment->isAvailable())
            ->values();

        $availableCourses = $this->courses->availableForOverview($trainee, $eligibleTrainingKeys);
        $progressByCourse = $availableCourses->mapWithKeys(function ($course) use ($trainee): array {
            $enrollment = $course->enrollments->first(fn ($item): bool => $item->status->grantsLearningAccess());

            if (! $enrollment) {
                return [$course->id => null];
            }

            $enrollment->setRelation('course', $course);

            return [$course->id => $this->learning->progress($enrollment, $trainee)];
        });

        return [
            'context' => 'Courses and tests available to you',
            'availableCourses' => $availableCourses,
            'progressByCourse' => $progressByCourse,
            'availableCategories' => $this->courses->availableCategoriesForOverview($eligibleTrainingKeys),
            'availableTests' => $tests,
            'trainingNames' => $this->trainingCatalog->all()->pluck('name', 'key')->all(),
            'creditAlerts' => $this->credits->dashboardData($trainee),
        ];
    }
}
