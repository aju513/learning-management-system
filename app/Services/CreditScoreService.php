<?php

namespace App\Services;

use App\Enums\CreditAwardStatus;
use App\Enums\CreditSourceType;
use App\Enums\FiscalYearStatus;
use App\Models\Assessment;
use App\Models\AttendanceSnapshot;
use App\Models\Course;
use App\Models\CourseAssessment;
use App\Models\CreditAward;
use App\Models\FiscalYear;
use App\Models\User;
use App\Repositories\Contracts\AssessmentRepositoryInterface;
use App\Repositories\Contracts\AttendanceSnapshotRepositoryInterface;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Repositories\Contracts\CreditAwardRepositoryInterface;
use App\Repositories\Contracts\FiscalYearRepositoryInterface;
use App\Services\Attendance\AttendanceProviderInterface;
use App\Services\Training\TrainingAvailabilityService;
use App\Services\Training\TrainingCatalogProviderInterface;
use DateTimeInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreditScoreService
{
    public function __construct(
        private readonly FiscalYearRepositoryInterface $fiscalYears,
        private readonly CreditAwardRepositoryInterface $awards,
        private readonly CourseRepositoryInterface $courses,
        private readonly AssessmentRepositoryInterface $assessments,
        private readonly AttendanceSnapshotRepositoryInterface $attendance,
        private readonly AttendanceProviderInterface $attendanceProvider,
        private readonly TrainingAvailabilityService $availability,
        private readonly TrainingCatalogProviderInterface $trainingCatalog,
    ) {}

    public function recordCourseCompletion(Course $course, User $trainee, ?DateTimeInterface $occurredAt = null): ?CreditAward
    {
        $points = (float) $course->credit_points;
        if ($points <= 0) {
            return null;
        }

        $fiscalYear = $this->fiscalYears->findContaining($occurredAt ?: now());
        if (! $fiscalYear) {
            return null;
        }

        return $this->createAwardIfMissing($fiscalYear, $trainee, CreditSourceType::CourseCompletion, $course->id, 'course:'.$course->id, $course->title, $points, $course->id);
    }

    public function courseAward(Course $course, User $trainee): ?CreditAward
    {
        return $this->awards->findCourseAward($course, $trainee);
    }

    public function recordAssessmentPass(Assessment|CourseAssessment $assessment, User $trainee, ?DateTimeInterface $occurredAt = null): ?CreditAward
    {
        $points = (float) $assessment->credit_points;
        if ($points <= 0) {
            return null;
        }

        $fiscalYear = $this->fiscalYears->findContaining($occurredAt ?: now());
        if (! $fiscalYear) {
            return null;
        }

        if ($assessment instanceof CourseAssessment) {
            $assessment->loadMissing('material.chapter.module.course');
            $sourceKey = 'course-assessment:'.$assessment->id;
            $course = $assessment->material->chapter->module->course;

            return $this->createAwardIfMissing($fiscalYear, $trainee, CreditSourceType::AssessmentPass, $assessment->id, $sourceKey, $assessment->material->title, $points, $course->id);
        }

        return $this->createAwardIfMissing($fiscalYear, $trainee, CreditSourceType::AssessmentPass, $assessment->id, 'assessment:'.$assessment->id, $assessment->title, $points, null, $assessment->id);
    }

    public function refreshAttendance(FiscalYear $fiscalYear, User $trainee): AttendanceSnapshot
    {
        if ($fiscalYear->status !== FiscalYearStatus::Active) {
            throw ValidationException::withMessages(['attendance' => 'Attendance can only be refreshed for the active fiscal year.']);
        }

        try {
            $presentDays = $this->attendanceProvider->presentDays($trainee, $fiscalYear);

            return DB::transaction(function () use ($fiscalYear, $trainee, $presentDays): AttendanceSnapshot {
                $snapshot = $this->attendance->upsert($fiscalYear, $trainee, [
                    'present_days' => max(0, $presentDays),
                    'source' => $this->attendanceProvider->name(),
                    'status' => 'success',
                    'error_message' => null,
                    'fetched_at' => now(),
                ]);
                if ($presentDays >= $fiscalYear->attendance_threshold_days && (float) $fiscalYear->attendance_credit_points > 0) {
                    $this->createAwardIfMissing($fiscalYear, $trainee, CreditSourceType::Attendance, null, 'attendance:'.$fiscalYear->id, 'Attendance threshold', (float) $fiscalYear->attendance_credit_points);
                }

                return $snapshot;
            });
        } catch (\Throwable $exception) {
            return $this->attendance->upsert($fiscalYear, $trainee, [
                'source' => $this->attendanceProvider->name(),
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'fetched_at' => now(),
            ]);
        }
    }

    public function claim(CreditAward $award, User $trainee): CreditAward
    {
        if ((int) $award->user_id !== (int) $trainee->id) {
            throw new AuthorizationException('You cannot claim another learner’s credit.');
        }

        return DB::transaction(function () use ($award, $trainee): CreditAward {
            $claimed = $this->awards->claim($award);
            activity('lms')->causedBy($trainee)->performedOn($claimed)->event('credit-award.claimed')
                ->withProperties(['fiscal_year_id' => $claimed->fiscal_year_id, 'points' => (float) $claimed->points, 'source_type' => $claimed->source_type->value])
                ->log('Credit award claimed');

            return $claimed;
        });
    }

    public function pageData(User $trainee): array
    {
        $fiscalYear = $this->fiscalYears->active();
        $eligibleTrainingKeys = $this->availability->eligibleTrainingKeys($trainee);

        return [
            'fiscalYear' => $fiscalYear,
            'attendance' => $fiscalYear ? $this->attendance->forUser($fiscalYear, $trainee) : null,
            'eligibleAwards' => $this->awards->eligibleForUser($trainee, $fiscalYear),
            'history' => $this->awards->forUser($trainee, $fiscalYear),
            'claimedTotal' => $this->awards->claimedTotal($trainee, $fiscalYear),
            'eligibleTotal' => $this->awards->eligibleTotal($trainee, $fiscalYear),
            'creditCourses' => $this->courses->creditCoursesForTrainee($trainee, $eligibleTrainingKeys, $fiscalYear?->id),
            'creditAssessments' => $this->assessments->creditAssessmentsForTrainee($trainee, $eligibleTrainingKeys, $fiscalYear?->id),
            'trainingNames' => $this->trainingCatalog->all()->pluck('name', 'key')->all(),
        ];
    }

    public function navbarSummary(User $trainee): array
    {
        $fiscalYear = $this->fiscalYears->active();

        return [
            'fiscalYear' => $fiscalYear,
            'claimedTotal' => $this->awards->claimedTotal($trainee, $fiscalYear),
            'eligibleCount' => $fiscalYear ? $this->awards->eligibleForUser($trainee, $fiscalYear, 1)->total() : 0,
        ];
    }

    public function dashboardData(User $trainee): array
    {
        $fiscalYear = $this->fiscalYears->active();

        return [
            'fiscalYear' => $fiscalYear,
            'eligibleTotal' => $fiscalYear ? $this->awards->eligibleTotal($trainee, $fiscalYear) : 0,
            'eligibleCount' => $fiscalYear ? $this->awards->eligibleForUser($trainee, $fiscalYear, 1)->total() : 0,
        ];
    }

    private function createAwardIfMissing(
        FiscalYear $fiscalYear,
        User $trainee,
        CreditSourceType $sourceType,
        ?int $sourceId,
        string $sourceKey,
        string $sourceLabel,
        float $points,
        ?int $courseId = null,
        ?int $assessmentId = null,
    ): CreditAward {
        $existing = $this->awards->findByKey($fiscalYear, $trainee, $sourceKey);
        if ($existing) {
            return $existing;
        }

        $award = $this->awards->create([
            'fiscal_year_id' => $fiscalYear->id,
            'user_id' => $trainee->id,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'source_key' => $sourceKey,
            'course_id' => $courseId,
            'assessment_id' => $assessmentId,
            'source_label' => $sourceLabel,
            'points' => $points,
            'status' => CreditAwardStatus::Eligible,
            'eligible_at' => now(),
        ]);
        activity('lms')->causedBy($trainee)->performedOn($award)->event('credit-award.eligible')
            ->withProperties(['fiscal_year_id' => $fiscalYear->id, 'points' => $points, 'source_type' => $sourceType->value])
            ->log('Credit award became eligible');

        return $award;
    }
}
