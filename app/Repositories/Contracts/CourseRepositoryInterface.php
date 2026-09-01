<?php

namespace App\Repositories\Contracts;

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseChapter;
use App\Models\CourseModule;
use App\Models\LearningMaterial;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CourseRepositoryInterface
{
    public function paginateCategories(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function activeCategories(): Collection;

    public function categorySlugExists(string $slug, ?CourseCategory $ignore = null): bool;

    public function createCategory(array $attributes): CourseCategory;

    public function updateCategory(CourseCategory $category, array $attributes): CourseCategory;

    public function categoryHasCourses(CourseCategory $category): bool;

    public function deleteCategory(CourseCategory $category): void;

    public function paginateCourses(array $filters, User $actor, int $perPage = 12): LengthAwarePaginator;

    public function paginatePublishedCatalog(array $filters, User $trainee, array $eligibleTrainingKeys = [], int $perPage = 12): LengthAwarePaginator;

    public function availableForOverview(User $trainee, array $eligibleTrainingKeys = [], int $limit = 8): Collection;

    public function suggestedForSummary(Course $currentCourse, User $trainee, array $eligibleTrainingKeys = [], int $limit = 4): Collection;

    public function availableCategoriesForOverview(array $eligibleTrainingKeys = [], int $limit = 4): Collection;

    public function creditCoursesForTrainee(User $trainee, array $eligibleTrainingKeys = [], ?int $fiscalYearId = null, int $limit = 12): Collection;

    public function findPublishedCatalogCourse(Course $course, User $trainee, array $eligibleTrainingKeys = []): Course;

    public function allPublishedCourses(): Collection;

    public function coursesForAuthoring(User $actor): Collection;

    public function coursesForApplicationReview(User $actor): Collection;

    public function courseSlugExists(string $slug, ?Course $ignore = null): bool;

    public function instructors(): Collection;

    public function findCourseDetails(Course $course): Course;

    public function findCourse(int $id): Course;

    public function createCourse(array $attributes): Course;

    public function updateCourse(Course $course, array $attributes): Course;

    public function deleteCourse(Course $course): void;

    public function courseHasEnrollments(Course $course): bool;

    public function findModule(int $id): CourseModule;

    public function findModuleDetails(CourseModule $module): CourseModule;

    public function createModule(array $attributes): CourseModule;

    public function updateModule(CourseModule $module, array $attributes): CourseModule;

    public function deleteModule(CourseModule $module): void;

    public function nextModulePosition(Course $course): int;

    public function adjacentModule(CourseModule $module, string $direction): ?CourseModule;

    public function moduleIds(Course $course): array;

    public function reorderModules(Course $course, array $moduleIds): void;

    public function findChapterDetails(CourseChapter $chapter): CourseChapter;

    public function createChapter(array $attributes): CourseChapter;

    public function updateChapter(CourseChapter $chapter, array $attributes): CourseChapter;

    public function chapterHasMaterials(CourseChapter $chapter): bool;

    public function deleteChapter(CourseChapter $chapter): void;

    public function nextChapterPosition(CourseModule $module): int;

    public function adjacentChapter(CourseChapter $chapter, string $direction): ?CourseChapter;

    public function chapterIds(CourseModule $module): array;

    public function reorderChapters(CourseModule $module, array $chapterIds): void;

    public function findMaterialDetails(LearningMaterial $material): LearningMaterial;

    public function createMaterial(array $attributes): LearningMaterial;

    public function updateMaterial(LearningMaterial $material, array $attributes): LearningMaterial;

    public function deleteMaterial(LearningMaterial $material): void;

    public function nextMaterialPosition(CourseChapter $chapter): int;

    public function adjacentMaterial(LearningMaterial $material, string $direction): ?LearningMaterial;

    public function materialIds(CourseChapter $chapter): array;

    public function reorderMaterials(CourseChapter $chapter, array $materialIds): void;
}
