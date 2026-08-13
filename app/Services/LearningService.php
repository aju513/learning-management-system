<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\MaterialType;
use App\Enums\NavigationMode;
use App\Models\Enrollment;
use App\Models\LearningMaterial;
use App\Models\User;
use App\Repositories\Contracts\AssessmentRepositoryInterface;
use App\Repositories\Contracts\EnrollmentRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class LearningService
{
    public function __construct(
        private readonly EnrollmentRepositoryInterface $enrollments,
        private readonly AssessmentRepositoryInterface $assessments,
    ) {}

    public function open(Enrollment $enrollment, LearningMaterial $material, User $trainee): array
    {
        $enrollment = $this->enrollments->findForLearning($enrollment);
        $materials = $enrollment->course->modules->flatMap->chapters->flatMap->materials->values();
        if (! $materials->contains('id', $material->id)) {
            throw new AuthorizationException('This material is not part of the enrolled course.');
        }
        $index = $materials->search(fn (LearningMaterial $item) => $item->id === $material->id);
        $material = $materials[$index];
        if ($enrollment->course->navigation_mode === NavigationMode::Sequential) {
            $completed = $enrollment->materialProgress->whereNotNull('completed_at')->pluck('learning_material_id');
            $locked = $materials->take($index)->contains(fn (LearningMaterial $item) => $item->is_required && ! $completed->contains($item->id));
            if ($locked) {
                throw new AuthorizationException('Complete the previous required material first.');
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
        ];
    }

    public function complete(Enrollment $enrollment, LearningMaterial $material, User $trainee): Enrollment
    {
        if ($material->type === MaterialType::Assessment && (! $material->assessment || ! $this->assessments->hasPassed($material->assessment, $trainee))) {
            throw new AuthorizationException('Pass the assessment before completing this material.');
        }

        return DB::transaction(function () use ($enrollment, $material, $trainee): Enrollment {
            $this->enrollments->completeMaterial($enrollment, $material);
            $enrollment = $this->recalculate($enrollment);
            activity('lms')->causedBy($trainee)->performedOn($material)->event('learning-material.completed')
                ->withProperties(['enrollment_id' => $enrollment->id, 'progress' => $enrollment->progress_percentage])->log('Learning material completed');

            return $enrollment;
        });
    }

    public function recalculate(Enrollment $enrollment): Enrollment
    {
        $total = $this->enrollments->requiredMaterialCount($enrollment);
        $completed = $this->enrollments->completedRequiredMaterialCount($enrollment);
        $percentage = $total === 0 ? 0 : round($completed / $total * 100, 2);
        $isComplete = $total > 0 && $completed >= $total;

        return $this->enrollments->update($enrollment, [
            'progress_percentage' => $percentage,
            'status' => $isComplete ? EnrollmentStatus::Completed : EnrollmentStatus::Active,
            'completed_at' => $isComplete ? ($enrollment->completed_at ?? now()) : null,
        ]);
    }
}
