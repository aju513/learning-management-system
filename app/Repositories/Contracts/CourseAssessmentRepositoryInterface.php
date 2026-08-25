<?php

namespace App\Repositories\Contracts;

use App\Models\CourseAssessment;
use App\Models\CourseAssessmentAnswer;
use App\Models\CourseAssessmentAttempt;
use App\Models\CourseAssessmentQuestion;
use App\Models\LearningMaterial;
use App\Models\User;

interface CourseAssessmentRepositoryInterface
{
    public function create(array $attributes): CourseAssessment;

    public function update(CourseAssessment $assessment, array $attributes): CourseAssessment;

    public function delete(CourseAssessment $assessment): void;

    public function findForMaterial(LearningMaterial $material): ?CourseAssessment;

    public function findForManagement(CourseAssessment $assessment): CourseAssessment;

    public function hasAttempts(CourseAssessment $assessment): bool;

    public function createQuestion(array $attributes): CourseAssessmentQuestion;

    public function findQuestionForEdit(CourseAssessmentQuestion $question): CourseAssessmentQuestion;

    public function updateQuestion(CourseAssessmentQuestion $question, array $attributes): CourseAssessmentQuestion;

    public function replaceOptions(CourseAssessmentQuestion $question, array $options): void;

    public function deleteQuestion(CourseAssessmentQuestion $question): void;

    public function nextQuestionPosition(CourseAssessment $assessment): int;

    public function questionIds(CourseAssessment $assessment): array;

    public function reorderQuestions(CourseAssessment $assessment, array $questionIds): void;

    public function createAttempt(array $attributes): CourseAssessmentAttempt;

    public function updateAttempt(CourseAssessmentAttempt $attempt, array $attributes): CourseAssessmentAttempt;

    public function findAttemptForTaking(CourseAssessmentAttempt $attempt): CourseAssessmentAttempt;

    public function findAttemptForSubmission(CourseAssessmentAttempt $attempt): CourseAssessmentAttempt;

    public function activeAttempt(CourseAssessment $assessment, User $trainee): ?CourseAssessmentAttempt;

    public function nextAttemptNumber(CourseAssessment $assessment, User $trainee): int;

    public function hasPassed(CourseAssessment $assessment, User $trainee): bool;

    public function createAnswer(array $attributes): CourseAssessmentAnswer;

    public function upsertAnswer(CourseAssessmentAttempt $attempt, CourseAssessmentQuestion $question, array $attributes): CourseAssessmentAnswer;
}
