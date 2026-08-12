<?php

namespace App\Repositories\Contracts;

use App\Models\Course;
use App\Models\CourseCategory;
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

    public function paginatePublishedCatalog(array $filters, User $trainee, int $perPage = 12): LengthAwarePaginator;

    public function findPublishedCatalogCourse(Course $course, User $trainee): Course;

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

    public function createMaterial(array $attributes): LearningMaterial;

    public function updateMaterial(LearningMaterial $material, array $attributes): LearningMaterial;

    public function deleteMaterial(LearningMaterial $material): void;

    public function nextMaterialPosition(CourseModule $module): int;

    public function adjacentMaterial(LearningMaterial $material, string $direction): ?LearningMaterial;
}
