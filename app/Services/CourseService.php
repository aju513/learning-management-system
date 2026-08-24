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
use App\Repositories\Contracts\CourseAssessmentRepositoryInterface;
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
        private readonly CourseAssessmentRepositoryInterface $courseAssessments,
        private readonly LearningMaterialImageService $materialImages,
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
            unset($data['available_to_all']);
            $thumbnail = Arr::pull($data, 'thumbnail');
            $data['slug'] = $this->uniqueCourseSlug($data['title']);
            $data['instructor_id'] = $actor->can('courses.edit-any') && filled($data['instructor_id'] ?? null)
                ? $data['instructor_id'] : $actor->id;
            $data['status'] = CourseStatus::Draft;
            $data['credit_points'] = $data['credit_points'] ?? 0;
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
            unset($data['available_to_all']);
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
            $issues = $this->publishingIssues($course);
            if ($issues !== []) {
                throw ValidationException::withMessages(['status' => $issues[0]['message']]);
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

    public function previewCourse(Course $course): Course
    {
        return $this->courses->findCourseDetails($course);
    }

    /** @return array<int, array<string, mixed>> */
    public function publishingIssues(Course $course): array
    {
        $issues = [];

        if ($course->modules->isEmpty()) {
            return [['message' => 'Add at least one module before publishing.']];
        }

        foreach ($course->modules as $module) {
            if ($module->chapters->isEmpty()) {
                $issues[] = [
                    'module_id' => $module->id,
                    'module' => $module,
                    'message' => "Module \"{$module->title}\" needs at least one chapter before publishing.",
                ];

                continue;
            }

            foreach ($module->chapters as $chapter) {
                if ($chapter->materials->isEmpty()) {
                    $issues[] = [
                        'module_id' => $module->id,
                        'chapter_id' => $chapter->id,
                        'module' => $module,
                        'chapter' => $chapter,
                        'message' => "Chapter \"{$chapter->title}\" needs at least one learning material before publishing.",
                    ];
                }

                foreach ($chapter->materials->where('type', MaterialType::CourseAssessment) as $material) {
                    $assessment = $material->courseAssessment;
                    $minimumQuestions = (int) config('lms.course_assessment_min_questions', 10);
                    if (! $assessment || $assessment->questions->count() < $minimumQuestions) {
                        $issues[] = [
                            'module_id' => $module->id,
                            'chapter_id' => $chapter->id,
                            'material_id' => $material->id,
                            'module' => $module,
                            'chapter' => $chapter,
                            'material' => $material,
                            'assessment' => $assessment,
                            'message' => "Course assessment \"{$material->title}\" needs {$minimumQuestions} questions before publishing (currently ".($assessment?->questions->count() ?? 0).').',
                        ];

                        continue;
                    }

                    foreach ($assessment->questions as $question) {
                        $correctCount = $question->options->where('is_correct', true)->count();
                        if ($question->options->count() < 2 || $correctCount < 1 || ($question->type->value === 'single_choice' && $correctCount !== 1)) {
                            $issues[] = [
                                'module_id' => $module->id,
                                'chapter_id' => $chapter->id,
                                'material_id' => $material->id,
                                'module' => $module,
                                'chapter' => $chapter,
                                'material' => $material,
                                'assessment' => $assessment,
                                'question' => $question,
                                'message' => "Question \"{$question->prompt}\" in \"{$material->title}\" needs valid answer options before publishing.",
                            ];
                        }
                    }
                }
            }
        }

        return $issues;
    }

    public function deleteCourse(Course $course, User $actor): void
    {
        if ($this->courses->courseHasEnrollments($course)) {
            throw ValidationException::withMessages(['course' => 'Archive courses with enrollment history instead of deleting them.']);
        }
        $course = $this->courses->findCourseDetails($course);
        $materialFiles = $course->modules->flatMap->chapters->flatMap->materials->pluck('file_path')->filter();
        $imageFiles = $course->modules->flatMap->chapters->flatMap(fn (CourseChapter $chapter) => $chapter->images->concat($chapter->materials->flatMap->images))->unique('id');
        DB::transaction(function () use ($course, $actor): void {
            activity('lms')->causedBy($actor)->performedOn($course)->event('course.deleted')
                ->withProperties(['title' => $course->title])->log('Course deleted');
            $this->courses->deleteCourse($course);
        });
        if ($course->thumbnail_path) {
            Storage::disk('public')->delete($course->thumbnail_path);
        }
        Storage::disk('local')->delete($materialFiles->all());
        $this->materialImages->deleteFiles($imageFiles);
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

    public function reorderModules(Course $course, array $moduleIds, User $actor): void
    {
        $existing = $this->courses->moduleIds($course);
        $submitted = array_map('intval', $moduleIds);
        if (count($existing) !== count($submitted) || array_diff($existing, $submitted) || array_diff($submitted, $existing)) {
            throw ValidationException::withMessages(['module_ids' => 'Submit every module from this course exactly once.']);
        }

        DB::transaction(fn () => $this->courses->reorderModules($course, $submitted));
        activity('lms')->causedBy($actor)->performedOn($course)->event('course-modules.reordered')->log('Course modules reordered');
    }

    public function deleteModule(CourseModule $module, User $actor): void
    {
        $module = $this->courses->findModuleDetails($module);
        $files = $module->chapters->flatMap->materials->pluck('file_path')->filter();
        $imageFiles = $module->chapters->flatMap(fn (CourseChapter $chapter) => $chapter->images->concat($chapter->materials->flatMap->images))->unique('id');
        activity('lms')->causedBy($actor)->performedOn($module)->event('course-module.deleted')
            ->withProperties(['title' => $module->title])->log('Course module deleted');
        $this->courses->deleteModule($module);
        Storage::disk('local')->delete($files->all());
        $this->materialImages->deleteFiles($imageFiles);
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

    public function reorderChapters(CourseModule $module, array $chapterIds, User $actor): void
    {
        $existing = $this->courses->chapterIds($module);
        $submitted = array_map('intval', $chapterIds);
        if (count($existing) !== count($submitted) || array_diff($existing, $submitted) || array_diff($submitted, $existing)) {
            throw ValidationException::withMessages(['chapter_ids' => 'Submit every chapter from this module exactly once.']);
        }

        DB::transaction(fn () => $this->courses->reorderChapters($module, $submitted));
        activity('lms')->causedBy($actor)->performedOn($module)->event('course-chapters.reordered')->log('Course chapters reordered');
    }

    public function deleteChapter(CourseChapter $chapter, User $actor): void
    {
        if ($this->courses->chapterHasMaterials($chapter)) {
            throw ValidationException::withMessages(['chapter' => 'Move or delete this chapter’s learning materials before deleting the chapter.']);
        }
        $images = $this->materialImages->deletePendingForChapter($chapter);
        activity('lms')->causedBy($actor)->performedOn($chapter)->event('course-chapter.deleted')
            ->withProperties(['title' => $chapter->title, 'course_module_id' => $chapter->course_module_id])->log('Course chapter deleted');
        $this->courses->deleteChapter($chapter);
        $this->materialImages->deleteFiles($images);
    }

    public function createMaterial(CourseChapter $chapter, array $data, User $actor): LearningMaterial
    {
        $data = $this->prepareMaterialData($data, $actor);
        $data['content'] = $this->materialImages->sanitizeContent($data['content'] ?? null, $chapter, $actor);
        $newFile = $data['file_path'] ?? null;

        try {
            return DB::transaction(function () use ($chapter, $data, $actor): LearningMaterial {
                $passingPercentage = Arr::pull($data, 'passing_percentage');
                $creditPoints = Arr::pull($data, 'credit_points', 0);
                $material = $this->courses->createMaterial([
                    ...$data,
                    'course_chapter_id' => $chapter->id,
                    'position' => $this->courses->nextMaterialPosition($chapter),
                ]);
                if ($material->type === MaterialType::CourseAssessment) {
                    $this->courseAssessments->create([
                        'learning_material_id' => $material->id,
                        'passing_percentage' => $passingPercentage,
                        'credit_points' => $creditPoints,
                    ]);
                }
                $this->materialImages->synchronize($material, $data['content'] ?? null, $chapter, $actor);
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
        $data['content'] = $this->materialImages->sanitizeContent($data['content'] ?? null, $material->chapter, $actor, $material);
        $newFile = $data['file_path'] ?? null;
        $removedImages = collect();

        try {
            $material = DB::transaction(function () use ($material, $data, $actor, &$removedImages): LearningMaterial {
                $passingPercentage = Arr::pull($data, 'passing_percentage');
                $creditPoints = Arr::pull($data, 'credit_points', 0);
                $existingCourseAssessment = $this->courseAssessments->findForMaterial($material);
                $material = $this->courses->updateMaterial($material, $data);
                if ($material->type === MaterialType::CourseAssessment) {
                    if ($existingCourseAssessment) {
                        $this->courseAssessments->update($existingCourseAssessment, ['passing_percentage' => $passingPercentage, 'credit_points' => $creditPoints]);
                    } else {
                        $this->courseAssessments->create(['learning_material_id' => $material->id, 'passing_percentage' => $passingPercentage, 'credit_points' => $creditPoints]);
                    }
                } elseif ($existingCourseAssessment) {
                    $this->courseAssessments->delete($existingCourseAssessment);
                }
                $removedImages = $this->materialImages->synchronize($material, $data['content'] ?? null, $material->chapter, $actor);
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
        $this->materialImages->deleteFiles($removedImages);

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

    public function reorderMaterials(CourseChapter $chapter, array $materialIds, User $actor): void
    {
        $existing = $this->courses->materialIds($chapter);
        $submitted = array_map('intval', $materialIds);
        if (count($existing) !== count($submitted) || array_diff($existing, $submitted) || array_diff($submitted, $existing)) {
            throw ValidationException::withMessages(['material_ids' => 'Submit every learning material from this chapter exactly once.']);
        }

        DB::transaction(fn () => $this->courses->reorderMaterials($chapter, $submitted));
        activity('lms')->causedBy($actor)->performedOn($chapter)->event('learning-materials.reordered')->log('Learning materials reordered');
    }

    public function deleteMaterial(LearningMaterial $material, User $actor): void
    {
        $file = $material->file_path;
        $images = DB::transaction(function () use ($material, $actor) {
            $images = $this->materialImages->deleteForMaterial($material);
            activity('lms')->causedBy($actor)->performedOn($material)->event('learning-material.deleted')
                ->withProperties(['title' => $material->title])->log('Learning material deleted');
            $this->courses->deleteMaterial($material);

            return $images;
        });
        if ($file) {
            Storage::disk('local')->delete($file);
        }
        $this->materialImages->deleteFiles($images);
    }

    private function prepareMaterialData(array $data, User $actor, ?LearningMaterial $material = null): array
    {
        $file = Arr::pull($data, 'file');
        $videoSource = Arr::pull($data, 'video_source');
        $type = MaterialType::from($data['type']);
        $typeChanged = $material && $material->type !== $type;
        $data['is_required'] = (bool) ($data['is_required'] ?? false);
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

        if ($type !== MaterialType::Video) {
            $data['video_url'] = null;
        }
        if ($type === MaterialType::Video && $videoSource === 'url') {
            $data['file_path'] = null;
            $data['original_filename'] = null;
            $data['mime_type'] = null;
        }
        if ($type === MaterialType::Video && $videoSource === 'upload') {
            $data['video_url'] = null;
        }
        if ($type !== MaterialType::Link) {
            $data['external_url'] = null;
        }
        if ($type !== MaterialType::File) {
            $data['file_type'] = null;
        }
        if ($type !== MaterialType::CourseAssessment) {
            unset($data['passing_percentage']);
        }

        return $data;
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
