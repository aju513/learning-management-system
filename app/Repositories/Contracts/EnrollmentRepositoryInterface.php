<?php

namespace App\Repositories\Contracts;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LearningMaterial;
use App\Models\MaterialProgress;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface EnrollmentRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function paginateApplications(array $filters, User $actor, int $perPage = 15): LengthAwarePaginator;

    public function paginateOwnedCourseProgress(array $filters, User $instructor, int $perPage = 15): LengthAwarePaginator;

    public function trainees(): Collection;

    public function traineesForAssessmentAssignment(User $actor): Collection;

    public function createOrRestore(Course $course, User $trainee, User $actor): Enrollment;

    public function createOrResetApplication(Course $course, User $trainee): Enrollment;

    public function update(Enrollment $enrollment, array $attributes): Enrollment;

    public function delete(Enrollment $enrollment): void;

    public function forTrainee(User $trainee): Collection;

    public function applicationsForTrainee(User $trainee): Collection;

    public function findForLearning(Enrollment $enrollment): Enrollment;

    public function findForCourseAndTrainee(Course $course, User $trainee): ?Enrollment;

    public function touchProgress(Enrollment $enrollment, LearningMaterial $material): MaterialProgress;

    public function completeMaterial(Enrollment $enrollment, LearningMaterial $material): MaterialProgress;

    public function requiredMaterialCount(Enrollment $enrollment): int;

    public function completedRequiredMaterialCount(Enrollment $enrollment): int;
}
