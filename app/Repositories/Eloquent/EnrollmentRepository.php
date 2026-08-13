<?php

namespace App\Repositories\Eloquent;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LearningMaterial;
use App\Models\MaterialProgress;
use App\Models\User;
use App\Repositories\Contracts\EnrollmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EnrollmentRepository implements EnrollmentRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Enrollment::query()->with(['course', 'trainee', 'assigner'])
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->whereHas('trainee', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when($filters['course_id'] ?? null, fn ($query, int|string $course) => $query->where('course_id', $course))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest('id')->paginate($perPage)->withQueryString();
    }

    public function paginateApplications(array $filters, User $actor, int $perPage = 15): LengthAwarePaginator
    {
        return Enrollment::query()
            ->whereIn('status', ['pending', 'rejected'])
            ->with(['course.instructor', 'trainee', 'reviewer'])
            ->when(! $actor->can('course-applications.review-all'), fn ($query) => $query->whereHas('course', fn ($course) => $course->where('instructor_id', $actor->id)))
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->whereHas('trainee', fn ($trainee) => $trainee->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when($filters['course_id'] ?? null, fn ($query, int|string $course) => $query->where('course_id', $course))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderBy('requested_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateOwnedCourseProgress(array $filters, User $instructor, int $perPage = 15): LengthAwarePaginator
    {
        return Enrollment::query()
            ->whereIn('status', ['active', 'completed'])
            ->whereHas('course', fn ($query) => $query->where('instructor_id', $instructor->id))
            ->with(['course', 'trainee'])
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->whereHas('trainee', fn ($trainee) => $trainee->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when($filters['course_id'] ?? null, fn ($query, int|string $course) => $query->where('course_id', $course))
            ->orderBy('enrolled_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function trainees(): Collection
    {
        return User::query()->role('trainee')->where('status', 'active')->orderBy('name')->get();
    }

    public function traineesForAssessmentAssignment(User $actor): Collection
    {
        return User::query()
            ->role('trainee')
            ->where('status', 'active')
            ->when(! $actor->can('course-applications.review-all'), fn ($query) => $query->whereHas('enrollments', fn ($enrollment) => $enrollment
                ->whereIn('status', ['active', 'completed'])
                ->whereHas('course', fn ($course) => $course->where('instructor_id', $actor->id))))
            ->orderBy('name')
            ->get();
    }

    public function createOrRestore(Course $course, User $trainee, User $actor): Enrollment
    {
        return Enrollment::query()->updateOrCreate(
            ['course_id' => $course->id, 'user_id' => $trainee->id],
            [
                'enrolled_by' => $actor->id,
                'reviewed_by' => null,
                'status' => 'active',
                'progress_percentage' => 0,
                'requested_at' => null,
                'enrolled_at' => now(),
                'reviewed_at' => null,
                'review_note' => null,
                'started_at' => null,
                'completed_at' => null,
            ],
        );
    }

    public function createOrResetApplication(Course $course, User $trainee): Enrollment
    {
        return Enrollment::query()->updateOrCreate(
            ['course_id' => $course->id, 'user_id' => $trainee->id],
            [
                'enrolled_by' => null,
                'reviewed_by' => null,
                'status' => 'pending',
                'progress_percentage' => 0,
                'requested_at' => now(),
                'enrolled_at' => null,
                'reviewed_at' => null,
                'review_note' => null,
                'started_at' => null,
                'completed_at' => null,
            ],
        );
    }

    public function update(Enrollment $enrollment, array $attributes): Enrollment
    {
        $enrollment->update($attributes);

        return $enrollment->refresh();
    }

    public function delete(Enrollment $enrollment): void
    {
        $enrollment->delete();
    }

    public function forTrainee(User $trainee): Collection
    {
        return Enrollment::query()->where('user_id', $trainee->id)->whereIn('status', ['active', 'completed'])
            ->with(['course.category', 'course.instructor', 'course.modules.chapters.materials'])->latest('enrolled_at')->get();
    }

    public function applicationsForTrainee(User $trainee): Collection
    {
        return Enrollment::query()
            ->where('user_id', $trainee->id)
            ->whereIn('status', ['pending', 'rejected', 'cancelled'])
            ->with(['course.category', 'course.instructor', 'reviewer'])
            ->latest('requested_at')
            ->get();
    }

    public function findForLearning(Enrollment $enrollment): Enrollment
    {
        return $enrollment->load(['course.modules.chapters.materials.assessment', 'materialProgress']);
    }

    public function findForCourseAndTrainee(Course $course, User $trainee): ?Enrollment
    {
        return Enrollment::query()->where('course_id', $course->id)->where('user_id', $trainee->id)
            ->whereIn('status', ['active', 'completed'])->first();
    }

    public function touchProgress(Enrollment $enrollment, LearningMaterial $material): MaterialProgress
    {
        return MaterialProgress::query()->updateOrCreate(
            ['enrollment_id' => $enrollment->id, 'learning_material_id' => $material->id],
            ['last_viewed_at' => now()],
        );
    }

    public function completeMaterial(Enrollment $enrollment, LearningMaterial $material): MaterialProgress
    {
        return MaterialProgress::query()->updateOrCreate(
            ['enrollment_id' => $enrollment->id, 'learning_material_id' => $material->id],
            ['last_viewed_at' => now(), 'completed_at' => now()],
        );
    }

    public function requiredMaterialCount(Enrollment $enrollment): int
    {
        return LearningMaterial::query()->whereHas('chapter.module', fn ($query) => $query->where('course_id', $enrollment->course_id))
            ->where('is_required', true)->count();
    }

    public function completedRequiredMaterialCount(Enrollment $enrollment): int
    {
        return MaterialProgress::query()->where('enrollment_id', $enrollment->id)->whereNotNull('completed_at')
            ->whereHas('material', fn ($query) => $query->where('is_required', true))->count();
    }
}
