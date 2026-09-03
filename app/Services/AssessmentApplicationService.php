<?php

namespace App\Services;

use App\Enums\AssessmentApplicationStatus;
use App\Models\Assessment;
use App\Models\AssessmentApplication;
use App\Models\User;
use App\Repositories\Contracts\AssessmentApplicationRepositoryInterface;
use App\Repositories\Contracts\AssessmentRepositoryInterface;
use App\Services\Training\TrainingAvailabilityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssessmentApplicationService
{
    public function __construct(
        private readonly AssessmentApplicationRepositoryInterface $applications,
        private readonly AssessmentRepositoryInterface $assessments,
        private readonly TrainingAvailabilityService $availability,
    ) {}

    public function apply(Assessment $assessment, User $trainee): AssessmentApplication
    {
        $assessment = $this->assessments->findForAvailability($assessment);
        if (! $assessment->isAvailable()) {
            throw ValidationException::withMessages(['assessment' => 'This test is not accepting applications.']);
        }
        $this->availability->assertAvailable($assessment, $trainee);

        if ($this->assessments->userCanTake($assessment, $trainee)) {
            throw ValidationException::withMessages(['assessment' => 'This test is already available in My Tests.']);
        }

        $existing = $this->applications->findForAssessmentAndTrainee($assessment, $trainee);
        if ($existing && in_array($existing->status, [AssessmentApplicationStatus::Pending, AssessmentApplicationStatus::Approved], true)) {
            throw ValidationException::withMessages(['assessment' => 'You already have an application for this test.']);
        }

        return DB::transaction(function () use ($assessment, $trainee): AssessmentApplication {
            $application = $this->applications->createOrReset($assessment, $trainee);
            activity('lms')->causedBy($trainee)->performedOn($application)->event('test-application.submitted')
                ->withProperties(['assessment_id' => $assessment->id])->log('Trainee applied for test');

            return $application;
        });
    }

    public function reviewIndex(User $actor, array $filters): array
    {
        return [
            'applications' => $this->applications->paginateForReview($filters, $actor),
            'assessments' => $this->applications->assessmentsForReview($actor),
        ];
    }

    public function approve(AssessmentApplication $application, User $reviewer): AssessmentApplication
    {
        return DB::transaction(function () use ($application, $reviewer): AssessmentApplication {
            $application = $this->applications->lockForReview($application);
            if ($application->status !== AssessmentApplicationStatus::Pending) {
                throw ValidationException::withMessages(['application' => 'Only pending test applications can be approved.']);
            }
            if (! $application->assessment->isAvailable()) {
                throw ValidationException::withMessages(['application' => 'The test must be published and open before this application can be approved.']);
            }
            if (! $application->trainee->isActive()) {
                throw ValidationException::withMessages(['application' => 'Only active trainees can be approved for a test.']);
            }
            $this->availability->assertAvailable($application->assessment, $application->trainee);

            $assignment = $this->assessments->assign(
                $application->assessment,
                $application->trainee,
                $reviewer,
                $application->assessment->ends_at?->toDateTimeString(),
            );
            $application = $this->applications->update($application, [
                'status' => AssessmentApplicationStatus::Approved,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => null,
            ]);
            activity('lms')->causedBy($reviewer)->performedOn($application)->event('test-application.approved')
                ->withProperties(['assessment_id' => $application->assessment_id, 'trainee_id' => $application->user_id, 'assignment_id' => $assignment->id])
                ->log('Test application approved');

            return $application;
        });
    }

    public function reject(AssessmentApplication $application, User $reviewer, ?string $note): AssessmentApplication
    {
        return DB::transaction(function () use ($application, $reviewer, $note): AssessmentApplication {
            $application = $this->applications->lockForReview($application);
            if ($application->status !== AssessmentApplicationStatus::Pending) {
                throw ValidationException::withMessages(['application' => 'Only pending test applications can be rejected.']);
            }

            $application = $this->applications->update($application, [
                'status' => AssessmentApplicationStatus::Rejected,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ]);
            activity('lms')->causedBy($reviewer)->performedOn($application)->event('test-application.rejected')
                ->withProperties(['assessment_id' => $application->assessment_id, 'trainee_id' => $application->user_id])
                ->log('Test application rejected');

            return $application;
        });
    }
}
