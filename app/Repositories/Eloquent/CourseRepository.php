<?php

namespace App\Repositories\Eloquent;

use App\Enums\UserStatus;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseChapter;
use App\Models\CourseModule;
use App\Models\LearningMaterial;
use App\Models\User;
use App\Repositories\Contracts\CourseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
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

    public function paginatePublishedCatalog(array $filters, User $trainee, array $eligibleTrainingKeys = [], int $perPage = 12): LengthAwarePaginator
    {
        return Course::query()
            ->where('status', 'published')
            ->where(function ($query) use ($eligibleTrainingKeys): void {
                $query->where('availability_scope', 'all')->orWhereNull('availability_scope')
                    ->orWhere(fn ($restricted) => $restricted->where('availability_scope', 'training')->whereIn('required_training_key', $eligibleTrainingKeys));
            })
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

    public function availableForOverview(User $trainee, array $eligibleTrainingKeys = [], int $limit = 8): Collection
    {
        return Course::query()
            ->where('status', 'published')
            ->where(function ($query) use ($eligibleTrainingKeys): void {
                $query->where('availability_scope', 'all')->orWhereNull('availability_scope')
                    ->orWhere(fn ($restricted) => $restricted->where('availability_scope', 'training')->whereIn('required_training_key', $eligibleTrainingKeys));
            })
            ->with([
                'category',
                'instructor',
                'enrollments' => fn ($query) => $query->where('user_id', $trainee->id)->with('materialProgress'),
                'modules.chapters.materials.courseAssessment.attempts' => fn ($query) => $query->where('user_id', $trainee->id),
            ])
            ->withCount('modules')
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }

    public function availableCategoriesForOverview(array $eligibleTrainingKeys = [], int $limit = 4): Collection
    {
        return CourseCategory::query()
            ->where('is_active', true)
            ->whereHas('courses', fn ($query) => $this->applyOverviewAvailability($query, $eligibleTrainingKeys))
            ->withCount(['courses' => fn ($query) => $this->applyOverviewAvailability($query, $eligibleTrainingKeys)])
            ->orderByDesc('courses_count')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    private function applyOverviewAvailability(Builder $query, array $eligibleTrainingKeys): void
    {
        $query->where('status', 'published')
            ->where(function ($availability) use ($eligibleTrainingKeys): void {
                $availability->where('availability_scope', 'all')->orWhereNull('availability_scope')
                    ->orWhere(fn ($restricted) => $restricted->where('availability_scope', 'training')->whereIn('required_training_key', $eligibleTrainingKeys));
            });
    }

    public function creditCoursesForTrainee(User $trainee, array $eligibleTrainingKeys = [], ?int $fiscalYearId = null, int $limit = 12): Collection
    {
        return Course::query()
            ->where('status', 'published')
            ->where('credit_points', '>', 0)
            ->where(function ($query) use ($eligibleTrainingKeys): void {
                $query->where('availability_scope', 'all')->orWhereNull('availability_scope')
                    ->orWhere(fn ($restricted) => $restricted->where('availability_scope', 'training')->whereIn('required_training_key', $eligibleTrainingKeys));
            })
            ->with([
                'category',
                'instructor',
                'enrollments' => fn ($query) => $query->where('user_id', $trainee->id)->whereIn('status', ['active', 'completed']),
                'creditAwards' => fn ($query) => $query->where('user_id', $trainee->id)->when($fiscalYearId, fn ($awardQuery) => $awardQuery->where('fiscal_year_id', $fiscalYearId)),
            ])
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }

    public function findPublishedCatalogCourse(Course $course, User $trainee, array $eligibleTrainingKeys = []): Course
    {
        $scope = $course->availability_scope?->value ?? $course->availability_scope;
        abort_unless($course->isPublished() && (in_array($scope, ['all', null], true)
            || ($scope === 'training' && in_array($course->required_training_key, $eligibleTrainingKeys, true))), 404);

        return $course->load([
            'category',
            'instructor',
            'modules.chapters.materials:id,course_chapter_id,title,type,duration_minutes,position,is_required',
            'modules.chapters.materials.courseAssessment:id,learning_material_id,passing_percentage',
            'modules.chapters.materials.courseAssessment.attempts',
            'enrollments' => fn ($query) => $query->where('user_id', $trainee->id)->with('materialProgress'),
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
            ->select(['id', 'title'])
            ->withCount([
                'enrollments as applied_count' => fn ($query) => $query->whereIn('status', ['pending', 'rejected']),
                'enrollments as accepted_count' => fn ($query) => $query->whereIn('status', ['active', 'completed']),
            ])
            ->when(! $actor->can('course-applications.review-all'), fn ($query) => $query->where('instructor_id', $actor->id))
            ->orderBy('title')
            ->get();
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
        return $course->load([
            'category', 'instructor', 'modules.chapters.images',
            'modules.chapters.materials.courseAssessment.questions.options',
            'modules.chapters.materials.images',
        ])
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
        return $module->load(['chapters.images', 'chapters.materials.images']);
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

    public function moduleIds(Course $course): array
    {
        return $course->modules()->orderBy('position')->pluck('id')->map(fn ($id): int => (int) $id)->all();
    }

    public function reorderModules(Course $course, array $moduleIds): void
    {
        foreach (array_values($moduleIds) as $position => $moduleId) {
            $course->modules()->whereKey($moduleId)->update(['position' => $position + 1]);
        }
    }

    public function findChapterDetails(CourseChapter $chapter): CourseChapter
    {
        return $chapter->load(['module.course', 'materials.courseAssessment', 'materials.images']);
    }

    public function createChapter(array $attributes): CourseChapter
    {
        return CourseChapter::query()->create($attributes);
    }

    public function updateChapter(CourseChapter $chapter, array $attributes): CourseChapter
    {
        $chapter->update($attributes);

        return $chapter->refresh();
    }

    public function chapterHasMaterials(CourseChapter $chapter): bool
    {
        return $chapter->materials()->exists();
    }

    public function deleteChapter(CourseChapter $chapter): void
    {
        $chapter->delete();
    }

    public function nextChapterPosition(CourseModule $module): int
    {
        return ((int) $module->chapters()->max('position')) + 1;
    }

    public function adjacentChapter(CourseChapter $chapter, string $direction): ?CourseChapter
    {
        return CourseChapter::query()->where('course_module_id', $chapter->course_module_id)
            ->where('position', $direction === 'up' ? '<' : '>', $chapter->position)
            ->orderBy('position', $direction === 'up' ? 'desc' : 'asc')->first();
    }

    public function chapterIds(CourseModule $module): array
    {
        return $module->chapters()->orderBy('position')->pluck('id')->map(fn ($id): int => (int) $id)->all();
    }

    public function reorderChapters(CourseModule $module, array $chapterIds): void
    {
        foreach (array_values($chapterIds) as $position => $chapterId) {
            $module->chapters()->whereKey($chapterId)->update(['position' => $position + 1]);
        }
    }

    public function findMaterialDetails(LearningMaterial $material): LearningMaterial
    {
        return $material->load(['chapter.module.course', 'courseAssessment', 'images']);
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

    public function nextMaterialPosition(CourseChapter $chapter): int
    {
        return ((int) $chapter->materials()->max('position')) + 1;
    }

    public function adjacentMaterial(LearningMaterial $material, string $direction): ?LearningMaterial
    {
        return LearningMaterial::query()->where('course_chapter_id', $material->course_chapter_id)
            ->where('position', $direction === 'up' ? '<' : '>', $material->position)
            ->orderBy('position', $direction === 'up' ? 'desc' : 'asc')->first();
    }

    public function materialIds(CourseChapter $chapter): array
    {
        return $chapter->materials()->orderBy('position')->pluck('id')->map(fn ($id): int => (int) $id)->all();
    }

    public function reorderMaterials(CourseChapter $chapter, array $materialIds): void
    {
        foreach (array_values($materialIds) as $position => $materialId) {
            $chapter->materials()->whereKey($materialId)->update(['position' => $position + 1]);
        }
    }
}
