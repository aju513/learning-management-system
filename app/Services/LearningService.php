<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\MaterialType;
use App\Enums\NavigationMode;
use App\Models\Enrollment;
use App\Models\LearningMaterial;
use App\Models\User;
use App\Repositories\Contracts\CourseAssessmentRepositoryInterface;
use App\Repositories\Contracts\EnrollmentRepositoryInterface;
use App\Services\Training\TrainingAvailabilityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class LearningService
{
    public function __construct(
        private readonly EnrollmentRepositoryInterface $enrollments,
        private readonly CourseAssessmentRepositoryInterface $courseAssessments,
        private readonly CreditScoreService $credits,
        private readonly TrainingAvailabilityService $availability,
    ) {}

    public function open(Enrollment $enrollment, LearningMaterial $material, User $trainee): array
    {
        $enrollment = $this->enrollments->findForLearning($enrollment);
        $this->assertPublishedCourse($enrollment);
        $this->availability->assertAvailable($enrollment->course, $trainee);
        $materials = $enrollment->course->modules->flatMap->chapters->flatMap->materials->values();
        if (! $materials->contains('id', $material->id)) {
            throw new AuthorizationException('This material is not part of the enrolled course.');
        }
        $index = $materials->search(fn (LearningMaterial $item) => (int) $item->id === (int) $material->id);
        $material = $materials[$index];
        $progress = $this->progress($enrollment, $trainee);
        $completed = $progress['completedIds'];
        if ($enrollment->course->navigation_mode === NavigationMode::Sequential) {
            $blockingMaterial = $materials->take($index)->first(fn (LearningMaterial $item) => $item->is_required && ! $completed->contains($item->id));
            if ($blockingMaterial) {
                return [
                    'enrollment' => $enrollment,
                    'material' => $material,
                    'previous' => $index > 0 ? $materials[$index - 1] : null,
                    'next' => null,
                    'locked' => true,
                    'blockingMaterial' => $blockingMaterial,
                    'progress' => $progress,
                ];
            }
        }
        $this->enrollments->touchProgress($enrollment, $material);
        if (! $enrollment->started_at) {
            $enrollment = $this->enrollments->update($enrollment, ['started_at' => now()]);
        }

        return [
            'enrollment' => $this->enrollments->findForLearning($enrollment),
            'material' => $material,
            'previous' => $index > 0 ? $materials[$index - 1] : null,
            'next' => $index < $materials->count() - 1 ? $materials[$index + 1] : null,
            'locked' => false,
            'blockingMaterial' => null,
            'progress' => $progress,
        ];
    }

    public function launch(Enrollment $enrollment, User $trainee): array
    {
        $enrollment = $this->enrollments->findForLearning($enrollment);
        $this->assertPublishedCourse($enrollment);
        $this->availability->assertAvailable($enrollment->course, $trainee);
        $materials = $enrollment->course->modules->flatMap->chapters->flatMap->materials->values();
        abort_unless($materials->isNotEmpty(), 404);

        $progress = $this->progress($enrollment, $trainee);
        if ($progress['isComplete']) {
            return [
                'summary' => true,
                'enrollment' => $enrollment,
                'progress' => $progress,
            ];
        }

        $material = $progress['nextMaterial'] ?? $materials->first();

        return $this->open($enrollment, $material, $trainee);
    }

    public function summary(Enrollment $enrollment, User $trainee): array
    {
        $enrollment = $this->enrollments->findForLearning($enrollment);
        $this->assertPublishedCourse($enrollment);
        $this->availability->assertAvailable($enrollment->course, $trainee);

        return [
            'enrollment' => $enrollment,
            'progress' => $this->progress($enrollment, $trainee),
        ];
    }

    /**
     * Return the single course-progress contract used by learner-facing pages.
     * Required course items include the course assessment, while lesson counts
     * intentionally exclude it for the supporting learning-material metric.
     */
    public function progress(Enrollment $enrollment, ?User $trainee = null): array
    {
        $materials = $enrollment->course->modules->flatMap->chapters->flatMap->materials->values();
        $requiredMaterials = $materials->where('is_required', true)->values();
        $lessonMaterials = $requiredMaterials->filter(fn (LearningMaterial $item) => $item->type !== MaterialType::CourseAssessment)->values();
        $completedIds = $enrollment->materialProgress->whereNotNull('completed_at')->pluck('learning_material_id');
        $completedCourseItems = $requiredMaterials->filter(fn (LearningMaterial $item) => $completedIds->contains($item->id))->count();
        $completedLessons = $lessonMaterials->filter(fn (LearningMaterial $item) => $completedIds->contains($item->id))->count();
        $assessmentMaterial = $materials->first(fn (LearningMaterial $item) => $item->type === MaterialType::CourseAssessment);
        $assessment = $assessmentMaterial?->courseAssessment;
        $attempts = $assessment?->attempts?->where('user_id', $enrollment->user_id) ?? collect();
        $latestAttempt = $attempts->sortByDesc('submitted_at')->first() ?? $attempts->sortByDesc('id')->first();
        $assessmentPassed = $attempts->contains(fn ($attempt) => (bool) $attempt->passed);
        $remainingItems = max(0, $requiredMaterials->count() - $completedCourseItems);
        $percentage = $requiredMaterials->count() > 0
            ? (int) round($completedCourseItems / $requiredMaterials->count() * 100)
            : 0;
        $lastViewed = $enrollment->materialProgress
            ->filter(fn ($item) => $item->last_viewed_at)
            ->sortByDesc('last_viewed_at')
            ->map(fn ($item) => $materials->firstWhere('id', $item->learning_material_id))
            ->filter()
            ->first();
        $lastViewedIncomplete = $enrollment->materialProgress
            ->filter(fn ($item) => $item->last_viewed_at && ! $completedIds->contains($item->learning_material_id))
            ->sortByDesc('last_viewed_at')
            ->map(fn ($item) => $requiredMaterials->firstWhere('id', $item->learning_material_id))
            ->filter()
            ->first();
        $creditAward = $this->credits->courseAward($enrollment->course, $trainee ?? $enrollment->trainee);

        return [
            'materials' => $materials,
            'requiredMaterials' => $requiredMaterials,
            'lessonMaterials' => $lessonMaterials,
            'completedIds' => $completedIds,
            'completed' => $completedCourseItems,
            'total' => $requiredMaterials->count(),
            'remaining' => $remainingItems,
            'percentage' => $percentage,
            'isComplete' => $requiredMaterials->isNotEmpty() && $remainingItems === 0,
            'completedLessons' => $completedLessons,
            'totalLessons' => $lessonMaterials->count(),
            'remainingLessons' => max(0, $lessonMaterials->count() - $completedLessons),
            'assessmentMaterial' => $assessmentMaterial,
            'assessment' => $assessment,
            'assessmentAttempts' => $attempts,
            'latestAssessmentAttempt' => $latestAttempt,
            'assessmentPassed' => $assessmentPassed,
            'assessmentStatus' => ! $assessment ? null : ($assessmentPassed ? 'Passed' : ($completedLessons < $lessonMaterials->count() ? 'Locked' : 'Available')),
            'nextMaterial' => $lastViewedIncomplete ?? $requiredMaterials->first(fn (LearningMaterial $item) => ! $completedIds->contains($item->id)),
            'lastViewed' => $lastViewed,
            'creditPoints' => (float) $enrollment->course->credit_points,
            'creditAward' => $creditAward,
        ];
    }

    public function complete(Enrollment $enrollment, LearningMaterial $material, User $trainee): array
    {
        $enrollment = $this->enrollments->findForLearning($enrollment);
        $this->assertPublishedCourse($enrollment);
        $this->availability->assertAvailable($enrollment->course, $trainee);

        if ($material->type === MaterialType::CourseAssessment && (! $material->courseAssessment || ! $this->courseAssessments->hasPassed($material->courseAssessment, $trainee))) {
            throw new AuthorizationException('Pass the course assessment before completing this material.');
        }

        return DB::transaction(function () use ($enrollment, $material, $trainee): array {
            $this->enrollments->completeMaterial($enrollment, $material);
            $enrollment = $this->recalculate($enrollment, $trainee);
            $award = $enrollment->status->value === 'completed'
                ? $this->credits->recordCourseCompletion($enrollment->course, $trainee, $enrollment->completed_at)
                : null;
            activity('lms')->causedBy($trainee)->performedOn($material)->event('learning-material.completed')
                ->withProperties(['enrollment_id' => $enrollment->id, 'progress' => $enrollment->progress_percentage])->log('Learning material completed');

            return ['enrollment' => $enrollment, 'creditAward' => $award];
        });
    }

    public function recalculate(Enrollment $enrollment, ?User $trainee = null): Enrollment
    {
        $enrollment = $this->enrollments->findForLearning($enrollment);
        $progress = $this->progress($enrollment, $trainee);
        $total = $progress['total'];
        $completed = $progress['completed'];
        $percentage = $total === 0 ? 0 : round($completed / $total * 100, 2);
        $isComplete = $total > 0 && $completed >= $total;

        $enrollment = $this->enrollments->update($enrollment, [
            'progress_percentage' => $percentage,
            'status' => $isComplete ? EnrollmentStatus::Completed : EnrollmentStatus::Active,
            'completed_at' => $isComplete ? ($enrollment->completed_at ?? now()) : null,
        ]);

        if ($isComplete) {
            $this->credits->recordCourseCompletion($enrollment->course, $enrollment->trainee, $enrollment->completed_at);
        }

        return $enrollment;
    }

    private function assertPublishedCourse(Enrollment $enrollment): void
    {
        if (! $enrollment->course->isPublished()) {
            throw new AuthorizationException('This course is no longer available for learning.');
        }
    }
}
