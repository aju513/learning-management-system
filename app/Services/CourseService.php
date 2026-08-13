<?php

namespace App\Services;

use App\Enums\CourseStatus;
use App\Enums\MaterialType;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseChapter;
use App\Models\CourseModule;
use App\Models\LearningMaterial;
use App\Models\User;
use App\Repositories\Contracts\AssessmentRepositoryInterface;
use App\Repositories\Contracts\CourseRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CourseService
{
    public function __construct(
        private readonly CourseRepositoryInterface $courses,
        private readonly AssessmentRepositoryInterface $assessments,
    ) {}

    public function createCategory(array $data, User $actor): CourseCategory
    {
        $data['slug'] = $this->uniqueCategorySlug($data['name']);
        $category = $this->courses->createCategory($data);
        activity('lms')->causedBy($actor)->performedOn($category)->event('course-category.created')->log('Course category created');

        return $category;
    }

    public function updateCategory(CourseCategory $category, array $data, User $actor): CourseCategory
    {
        $data['slug'] = $this->uniqueCategorySlug($data['name'], $category);
        $category = $this->courses->updateCategory($category, $data);
        activity('lms')->causedBy($actor)->performedOn($category)->event('course-category.updated')->log('Course category updated');

        return $category;
    }

    public function deleteCategory(CourseCategory $category, User $actor): void
    {
        if ($this->courses->categoryHasCourses($category)) {
            throw ValidationException::withMessages(['category' => 'Move or delete the category courses before deleting this category.']);
        }
        activity('lms')->causedBy($actor)->performedOn($category)->event('course-category.deleted')
            ->withProperties(['name' => $category->name])->log('Course category deleted');
        $this->courses->deleteCategory($category);
    }

    public function createCourse(array $data, User $actor): Course
    {
        return DB::transaction(function () use ($data, $actor): Course {
            $thumbnail = Arr::pull($data, 'thumbnail');
            $data['slug'] = $this->uniqueCourseSlug($data['title']);
            $data['instructor_id'] = $actor->can('courses.edit-any') && filled($data['instructor_id'] ?? null)
                ? $data['instructor_id'] : $actor->id;
            $data['status'] = CourseStatus::Draft;
            if ($thumbnail instanceof UploadedFile) {
                $data['thumbnail_path'] = $thumbnail->store('lms/thumbnails', 'public');
            }
            $course = $this->courses->createCourse($data);
            activity('lms')->causedBy($actor)->performedOn($course)->event('course.created')
                ->withProperties(['title' => $course->title, 'instructor_id' => $course->instructor_id])->log('Course created');

            return $course;
        });
    }

    public function updateCourse(Course $course, array $data, User $actor): Course
    {
        return DB::transaction(function () use ($course, $data, $actor): Course {
            $oldThumbnail = $course->thumbnail_path;
            $thumbnail = Arr::pull($data, 'thumbnail');
            $data['slug'] = $this->uniqueCourseSlug($data['title'], $course);
            if (! $actor->can('courses.edit-any')) {
                unset($data['instructor_id']);
            }
            if ($thumbnail instanceof UploadedFile) {
                $data['thumbnail_path'] = $thumbnail->store('lms/thumbnails', 'public');
            }
            $course = $this->courses->updateCourse($course, $data);
            if ($thumbnail instanceof UploadedFile && $oldThumbnail) {
                Storage::disk('public')->delete($oldThumbnail);
            }
            activity('lms')->causedBy($actor)->performedOn($course)->event('course.updated')
                ->withProperties(['title' => $course->title])->log('Course updated');

            return $course;
        });
    }

    public function changeStatus(Course $course, CourseStatus $status, User $actor): Course
    {
        if ($status === CourseStatus::Published) {
            $course = $this->courses->findCourseDetails($course);
            if ($course->modules->isEmpty()) {
                throw ValidationException::withMessages(['status' => 'Add at least one module before publishing.']);
            }
            if ($course->modules->contains(fn (CourseModule $module) => $module->chapters->isEmpty())) {
                throw ValidationException::withMessages(['status' => 'Every module needs at least one chapter before publishing.']);
            }
            if ($course->modules->flatMap->chapters->contains(fn (CourseChapter $chapter) => $chapter->materials->isEmpty())) {
                throw ValidationException::withMessages(['status' => 'Every chapter needs at least one learning material before publishing.']);
            }
        }
        $course = $this->courses->updateCourse($course, [
            'status' => $status,
            'published_at' => $status === CourseStatus::Published ? ($course->published_at ?? now()) : $course->published_at,
        ]);
        activity('lms')->causedBy($actor)->performedOn($course)->event('course.status-changed')
            ->withProperties(['status' => $status->value])->log('Course status changed');

        return $course;
    }

    public function deleteCourse(Course $course, User $actor): void
    {
        if ($this->courses->courseHasEnrollments($course)) {
            throw ValidationException::withMessages(['course' => 'Archive courses with enrollment history instead of deleting them.']);
        }
        $course = $this->courses->findCourseDetails($course);
        $materialFiles = $course->modules->flatMap->chapters->flatMap->materials->pluck('file_path')->filter();
        DB::transaction(function () use ($course, $actor): void {
            activity('lms')->causedBy($actor)->performedOn($course)->event('course.deleted')
                ->withProperties(['title' => $course->title])->log('Course deleted');
            $this->courses->deleteCourse($course);
        });
        if ($course->thumbnail_path) {
            Storage::disk('public')->delete($course->thumbnail_path);
        }
        Storage::disk('local')->delete($materialFiles->all());
    }

    public function createModule(Course $course, array $data, User $actor): CourseModule
    {
        return DB::transaction(function () use ($course, $data, $actor): CourseModule {
            $module = $this->courses->createModule([...$data, 'course_id' => $course->id, 'position' => $this->courses->nextModulePosition($course)]);
            $chapter = $this->courses->createChapter([
                'course_module_id' => $module->id,
                'title' => 'Chapter 1',
                'position' => 1,
            ]);
            activity('lms')->causedBy($actor)->performedOn($module)->event('course-module.created')
                ->withProperties(['course_id' => $course->id])->log('Course module created');
            activity('lms')->causedBy($actor)->performedOn($chapter)->event('course-chapter.created')
                ->withProperties(['course_module_id' => $module->id, 'automatic' => true])->log('Default course chapter created');

            return $module;
        });
    }

    public function updateModule(CourseModule $module, array $data, User $actor): CourseModule
    {
        $module = $this->courses->updateModule($module, $data);
        activity('lms')->causedBy($actor)->performedOn($module)->event('course-module.updated')->log('Course module updated');

        return $module;
    }

    public function moveModule(CourseModule $module, string $direction, User $actor): void
    {
        DB::transaction(function () use ($module, $direction, $actor): void {
            $adjacent = $this->courses->adjacentModule($module, $direction);
            if (! $adjacent) {
                return;
            }
            $position = $module->position;
            $this->courses->updateModule($module, ['position' => $adjacent->position]);
            $this->courses->updateModule($adjacent, ['position' => $position]);
            activity('lms')->causedBy($actor)->performedOn($module)->event('course-module.reordered')
                ->withProperties(['direction' => $direction])->log('Course module reordered');
        });
    }

    public function deleteModule(CourseModule $module, User $actor): void
    {
        $module = $this->courses->findModuleDetails($module);
        $files = $module->chapters->flatMap->materials->pluck('file_path')->filter();
        activity('lms')->causedBy($actor)->performedOn($module)->event('course-module.deleted')
            ->withProperties(['title' => $module->title])->log('Course module deleted');
        $this->courses->deleteModule($module);
        Storage::disk('local')->delete($files->all());
    }

    public function createChapter(CourseModule $module, array $data, User $actor): CourseChapter
    {
        $chapter = $this->courses->createChapter([
            ...$data,
            'course_module_id' => $module->id,
            'position' => $this->courses->nextChapterPosition($module),
        ]);
        activity('lms')->causedBy($actor)->performedOn($chapter)->event('course-chapter.created')
            ->withProperties(['course_module_id' => $module->id])->log('Course chapter created');

        return $chapter;
    }

    public function updateChapter(CourseChapter $chapter, array $data, User $actor): CourseChapter
    {
        $chapter = $this->courses->updateChapter($chapter, $data);
        activity('lms')->causedBy($actor)->performedOn($chapter)->event('course-chapter.updated')->log('Course chapter updated');

        return $chapter;
    }

    public function moveChapter(CourseChapter $chapter, string $direction, User $actor): void
    {
        DB::transaction(function () use ($chapter, $direction, $actor): void {
            $adjacent = $this->courses->adjacentChapter($chapter, $direction);
            if (! $adjacent) {
                return;
            }
            $position = $chapter->position;
            $this->courses->updateChapter($chapter, ['position' => $adjacent->position]);
            $this->courses->updateChapter($adjacent, ['position' => $position]);
            activity('lms')->causedBy($actor)->performedOn($chapter)->event('course-chapter.reordered')
                ->withProperties(['direction' => $direction])->log('Course chapter reordered');
        });
    }

    public function deleteChapter(CourseChapter $chapter, User $actor): void
    {
        if ($this->courses->chapterHasMaterials($chapter)) {
            throw ValidationException::withMessages(['chapter' => 'Move or delete this chapter’s learning materials before deleting the chapter.']);
        }
        activity('lms')->causedBy($actor)->performedOn($chapter)->event('course-chapter.deleted')
            ->withProperties(['title' => $chapter->title, 'course_module_id' => $chapter->course_module_id])->log('Course chapter deleted');
        $this->courses->deleteChapter($chapter);
    }

    public function createMaterial(CourseChapter $chapter, array $data, User $actor): LearningMaterial
    {
        $data = $this->prepareMaterialData($data, $actor);
        $newFile = $data['file_path'] ?? null;

        try {
            return DB::transaction(function () use ($chapter, $data, $actor): LearningMaterial {
                $material = $this->courses->createMaterial([
                    ...$data,
                    'course_chapter_id' => $chapter->id,
                    'position' => $this->courses->nextMaterialPosition($chapter),
                ]);
                activity('lms')->causedBy($actor)->performedOn($material)->event('learning-material.created')
                    ->withProperties(['course_id' => $chapter->module->course_id, 'chapter_id' => $chapter->id, 'type' => $material->type->value])->log('Learning material created');

                return $material;
            });
        } catch (\Throwable $exception) {
            if ($newFile) {
                Storage::disk('local')->delete($newFile);
            }

            throw $exception;
        }
    }

    public function updateMaterial(LearningMaterial $material, array $data, User $actor): LearningMaterial
    {
        $oldFile = $material->file_path;
        $data = $this->prepareMaterialData($data, $actor, $material);
        $newFile = $data['file_path'] ?? null;

        try {
            $material = DB::transaction(function () use ($material, $data, $actor): LearningMaterial {
                $material = $this->courses->updateMaterial($material, $data);
                activity('lms')->causedBy($actor)->performedOn($material)->event('learning-material.updated')->log('Learning material updated');

                return $material;
            });
        } catch (\Throwable $exception) {
            if ($newFile && $newFile !== $oldFile) {
                Storage::disk('local')->delete($newFile);
            }

            throw $exception;
        }

        if (array_key_exists('file_path', $data) && $oldFile && $oldFile !== $material->file_path) {
            Storage::disk('local')->delete($oldFile);
        }

        return $material;
    }

    public function moveMaterial(LearningMaterial $material, string $direction, User $actor): void
    {
        DB::transaction(function () use ($material, $direction, $actor): void {
            $adjacent = $this->courses->adjacentMaterial($material, $direction);
            if (! $adjacent) {
                return;
            }
            $position = $material->position;
            $this->courses->updateMaterial($material, ['position' => $adjacent->position]);
            $this->courses->updateMaterial($adjacent, ['position' => $position]);
            activity('lms')->causedBy($actor)->performedOn($material)->event('learning-material.reordered')
                ->withProperties(['direction' => $direction])->log('Learning material reordered');
        });
    }

    public function deleteMaterial(LearningMaterial $material, User $actor): void
    {
        $file = $material->file_path;
        activity('lms')->causedBy($actor)->performedOn($material)->event('learning-material.deleted')
            ->withProperties(['title' => $material->title])->log('Learning material deleted');
        $this->courses->deleteMaterial($material);
        if ($file) {
            Storage::disk('local')->delete($file);
        }
    }

    private function prepareMaterialData(array $data, User $actor, ?LearningMaterial $material = null): array
    {
        $file = Arr::pull($data, 'file');
        $type = MaterialType::from($data['type']);
        $typeChanged = $material && $material->type !== $type;
        if ($type === MaterialType::Assessment && filled($data['assessment_id'] ?? null)) {
            $assessment = $this->assessments->findAssessment((int) $data['assessment_id']);
            if (! $actor->can('assessments.edit-any') && $assessment->created_by !== $actor->id) {
                throw ValidationException::withMessages(['assessment_id' => 'You can only attach an assessment you own.']);
            }
        }
        $data['is_required'] = (bool) ($data['is_required'] ?? false);
        if ($type === MaterialType::Article && filled($data['content'] ?? null)) {
            $data['content'] = $this->sanitizeArticle($data['content']);
        }
        if ($file instanceof UploadedFile) {
            $data['file_path'] = $file->store('lms/materials');
            $data['original_filename'] = $file->getClientOriginalName();
            $data['mime_type'] = $file->getMimeType();
        }
        if (! $type->supportsFile() || ($typeChanged && ! ($file instanceof UploadedFile))) {
            $data['file_path'] = null;
            $data['original_filename'] = null;
            $data['mime_type'] = null;
        }

        if ($type !== MaterialType::Article) {
            $data['content'] = null;
        }
        if (! in_array($type, [MaterialType::Video, MaterialType::ExternalLink], true)) {
            $data['external_url'] = null;
        }
        if ($type !== MaterialType::Assessment) {
            $data['assessment_id'] = null;
        }

        return $data;
    }

    private function sanitizeArticle(string $html): string
    {
        $clean = strip_tags($html, '<p><br><strong><b><em><i><ul><ol><li><h2><h3><blockquote>');

        return (string) preg_replace('/<([a-z][a-z0-9]*)\b[^>]*>/i', '<$1>', $clean);
    }

    private function uniqueCategorySlug(string $name, ?CourseCategory $ignore = null): string
    {
        return $this->uniqueSlug($name, fn (string $slug): bool => $this->courses->categorySlugExists($slug, $ignore));
    }

    private function uniqueCourseSlug(string $title, ?Course $ignore = null): string
    {
        return $this->uniqueSlug($title, fn (string $slug): bool => $this->courses->courseSlugExists($slug, $ignore));
    }

    private function uniqueSlug(string $value, callable $exists): string
    {
        $base = Str::slug($value) ?: 'item';
        $slug = $base;
        $suffix = 2;
        while ($exists($slug)) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
