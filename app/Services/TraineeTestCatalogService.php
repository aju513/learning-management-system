<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\AssessmentRepositoryInterface;
use App\Services\Training\TrainingAvailabilityService;

class TraineeTestCatalogService
{
    public function __construct(
        private readonly AssessmentRepositoryInterface $assessments,
        private readonly TrainingAvailabilityService $availability,
    ) {}

    public function index(User $trainee, array $filters): array
    {
        $eligibleTrainingKeys = $this->availability->eligibleTrainingKeys($trainee);

        return [
            'assessments' => $this->assessments->paginatePublishedCatalog($filters, $trainee, $eligibleTrainingKeys),
            'categories' => $this->assessments->activeCategories(),
            'availableCategories' => $this->assessments->availableCategoriesForCatalog($trainee, $eligibleTrainingKeys),
        ];
    }

    public function show(User $trainee, int $assessmentId): array
    {
        $eligibleTrainingKeys = $this->availability->eligibleTrainingKeys($trainee);
        $assessment = $this->assessments->findPublishedCatalogAssessment(
            $this->assessments->findAssessment($assessmentId),
            $trainee,
            $eligibleTrainingKeys,
        );

        return ['assessment' => $assessment];
    }
}
