<?php

namespace App\Repositories\Eloquent;

use App\Enums\UserStatus;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseModule;
use App\Models\LearningMaterial;
use App\Models\User;
use App\Repositories\Contracts\CourseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CourseRepository implements CourseRepositoryInterface
{
    public function paginateCategories(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return CourseCategory::query()->withCount('courses')
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')->paginate($perPage)->withQueryString();
    }

    public function activeCategories(): Collection
    {
        return CourseCategory::query()->where('is_active', true)->orderBy('name')->get();
    }

    public function categorySlugExists(string $slug, ?CourseCategory $ignore = null): bool
    {
        return CourseCategory::query()->where('slug', $slug)
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))->exists();
    }

    public function createCategory(array $attributes): CourseCategory
    {
        return CourseCategory::query()->create($attributes);
    }

    public function updateCategory(CourseCategory $category, array $attributes): CourseCategory
    {
        $category->update($attributes);

        return $category->refresh();
    }

    public function categoryHasCourses(CourseCategory $category): bool
    {
        return $category->courses()->exists();
    }

    public function deleteCategory(CourseCategory $category): void
    {
        $category->delete();
    }

    public function paginateCourses(array $filters, User $actor, int $perPage = 12): LengthAwarePaginator
    {
        return Course::query()->with(['category', 'instructor'])->withCount(['modules', 'enrollments'])
            ->when(! $actor->can('courses.view-all'), fn ($query) => $query->where('instructor_id', $actor->id))
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where('title', 'like', "%{$search}%"))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['category_id'] ?? null, fn ($query, int|string $category) => $query->where('category_id', $category))
            ->latest('id')->paginate($perPage)->withQueryString();
    }

    public function paginatePublishedCatalog(array $filters, User $trainee, int $perPage = 12): LengthAwarePaginator
    {
        return Course::query()
            ->where('status', 'published')
            ->with(['category', 'instructor', 'enrollments' => fn ($query) => $query->where('user_id', $trainee->id)])
            ->withCount('modules')
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                $query->where('title', 'like', "%{$search}%")->orWhere('short_description', 'like', "%{$search}%");
            }))
            ->when($filters['category_id'] ?? null, fn ($query, int|string $category) => $query->where('category_id', $category))
            ->when($filters['difficulty'] ?? null, fn ($query, string $difficulty) => $query->where('difficulty', $difficulty))
            ->latest('published_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findPublishedCatalogCourse(Course $course, User $trainee): Course
    {
        abort_unless($course->isPublished(), 404);

        return $course->load([
            'category',
            'instructor',
            'modules.materials:id,course_module_id,title,type,duration_minutes,position',
            'enrollments' => fn ($query) => $query->where('user_id', $trainee->id),
        ]);
    }

    public function allPublishedCourses(): Collection
    {
        return Course::query()->where('status', 'published')->orderBy('title')->get();
    }

    public function coursesForAuthoring(User $actor): Collection
    {
        return Course::query()->with('modules')
            ->when(! $actor->can('courses.edit-any'), fn ($query) => $query->where('instructor_id', $actor->id))
            ->orderBy('title')->get();
    }

    public function coursesForApplicationReview(User $actor): Collection
    {
        return Course::query()
            ->when(! $actor->can('course-applications.review-all'), fn ($query) => $query->where('instructor_id', $actor->id))
            ->orderBy('title')
            ->get(['id', 'title']);
    }

    public function courseSlugExists(string $slug, ?Course $ignore = null): bool
    {
        return Course::query()->where('slug', $slug)
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))->exists();
    }

    public function instructors(): Collection
    {
        return User::query()->permission('courses.create')->where('status', UserStatus::Active)->orderBy('name')->get();
    }

    public function findCourseDetails(Course $course): Course
    {
        return $course->load(['category', 'instructor', 'modules.materials.assessment', 'assessments'])
            ->loadCount('enrollments');
    }

    public function findCourse(int $id): Course
    {
        return Course::query()->findOrFail($id);
    }

    public function createCourse(array $attributes): Course
    {
        return Course::query()->create($attributes);
    }

    public function updateCourse(Course $course, array $attributes): Course
    {
        $course->update($attributes);

        return $course->refresh();
    }

    public function deleteCourse(Course $course): void
    {
        $course->delete();
    }

    public function courseHasEnrollments(Course $course): bool
    {
        return $course->enrollments()->exists();
    }

    public function findModule(int $id): CourseModule
    {
        return CourseModule::query()->with('course')->findOrFail($id);
    }

    public function findModuleDetails(CourseModule $module): CourseModule
    {
        return $module->load('materials');
    }

    public function createModule(array $attributes): CourseModule
    {
        return CourseModule::query()->create($attributes);
    }

    public function updateModule(CourseModule $module, array $attributes): CourseModule
    {
        $module->update($attributes);

        return $module->refresh();
    }

    public function deleteModule(CourseModule $module): void
    {
        $module->delete();
    }

    public function nextModulePosition(Course $course): int
    {
        return ((int) $course->modules()->max('position')) + 1;
    }

    public function adjacentModule(CourseModule $module, string $direction): ?CourseModule
    {
        return CourseModule::query()->where('course_id', $module->course_id)
            ->where('position', $direction === 'up' ? '<' : '>', $module->position)
            ->orderBy('position', $direction === 'up' ? 'desc' : 'asc')->first();
    }

    public function createMaterial(array $attributes): LearningMaterial
    {
        return LearningMaterial::query()->create($attributes);
    }

    public function updateMaterial(LearningMaterial $material, array $attributes): LearningMaterial
    {
        $material->update($attributes);

        return $material->refresh();
    }

    public function deleteMaterial(LearningMaterial $material): void
    {
        $material->delete();
    }

    public function nextMaterialPosition(CourseModule $module): int
    {
        return ((int) $module->materials()->max('position')) + 1;
    }

    public function adjacentMaterial(LearningMaterial $material, string $direction): ?LearningMaterial
    {
        return LearningMaterial::query()->where('course_module_id', $material->course_module_id)
            ->where('position', $direction === 'up' ? '<' : '>', $material->position)
            ->orderBy('position', $direction === 'up' ? 'desc' : 'asc')->first();
    }
}
