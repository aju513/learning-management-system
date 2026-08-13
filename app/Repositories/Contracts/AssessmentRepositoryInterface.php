<?php

namespace App\Repositories\Contracts;

use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\AttemptAnswer;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AssessmentRepositoryInterface
{
    public function paginate(array $filters, User $actor, int $perPage = 15): LengthAwarePaginator;

    public function findForManagement(Assessment $assessment): Assessment;

    public function findAssessment(int $id): Assessment;

    public function attachable(User $actor): Collection;

    public function allForFilter(): Collection;

    public function create(array $attributes): Assessment;

    public function update(Assessment $assessment, array $attributes): Assessment;

    public function delete(Assessment $assessment): void;

    public function hasAttempts(Assessment $assessment): bool;

    public function hasAttachedMaterials(Assessment $assessment): bool;

    public function findForAvailability(Assessment $assessment): Assessment;

    public function materialsFor(Assessment $assessment): Collection;

    public function createQuestion(array $attributes): AssessmentQuestion;

    public function findQuestionForEdit(AssessmentQuestion $question): AssessmentQuestion;

    public function updateQuestion(AssessmentQuestion $question, array $attributes): AssessmentQuestion;

    public function replaceOptions(AssessmentQuestion $question, array $options): void;

    public function deleteQuestion(AssessmentQuestion $question): void;

    public function nextQuestionPosition(Assessment $assessment): int;

    public function questionIds(Assessment $assessment): array;

    public function reorderQuestions(Assessment $assessment, array $questionIds): void;

    public function assign(Assessment $assessment, User $trainee, User $actor, ?string $dueAt): AssessmentAssignment;

    public function unassign(AssessmentAssignment $assignment): void;

    public function availableFor(User $trainee): Collection;

    public function userCanTake(Assessment $assessment, User $trainee): bool;

    public function countAttempts(Assessment $assessment, User $trainee): int;

    public function activeAttempt(Assessment $assessment, User $trainee): ?AssessmentAttempt;

    public function hasPassed(Assessment $assessment, User $trainee): bool;

    public function createAttempt(array $attributes): AssessmentAttempt;

    public function findAttemptForTaking(AssessmentAttempt $attempt): AssessmentAttempt;

    public function createAnswer(array $attributes): AttemptAnswer;

    public function updateAnswer(AttemptAnswer $answer, array $attributes): AttemptAnswer;

    public function updateAttempt(AssessmentAttempt $attempt, array $attributes): AssessmentAttempt;

    public function paginateResults(array $filters, User $actor, int $perPage = 15): LengthAwarePaginator;
}
