<?php

namespace App\Repositories\Contracts;

use App\Models\Assessment;
use App\Models\AssessmentApplication;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AssessmentApplicationRepositoryInterface
{
    public function findForAssessmentAndTrainee(Assessment $assessment, User $trainee): ?AssessmentApplication;

    public function createOrReset(Assessment $assessment, User $trainee): AssessmentApplication;

    public function update(AssessmentApplication $application, array $attributes): AssessmentApplication;

    public function lockForReview(AssessmentApplication $application): AssessmentApplication;

    public function paginateForReview(array $filters, User $actor, int $perPage = 15): LengthAwarePaginator;

    public function assessmentsForReview(User $actor): Collection;
}
