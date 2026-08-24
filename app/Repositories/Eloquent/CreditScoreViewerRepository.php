<?php

namespace App\Repositories\Eloquent;

use App\Enums\CreditAwardStatus;
use App\Enums\CreditSourceType;
use App\Models\AssessmentAttempt;
use App\Models\AttendanceSnapshot;
use App\Models\CourseAssessmentAttempt;
use App\Models\CreditAward;
use App\Models\Enrollment;
use App\Models\FiscalYear;
use App\Models\User;
use App\Repositories\Contracts\CreditScoreViewerRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CreditScoreViewerRepository implements CreditScoreViewerRepositoryInterface
{
    public function fiscalYears(): Collection
    {
        return FiscalYear::query()->orderByDesc('starts_on')->get();
    }

    public function findFiscalYear(?int $fiscalYearId = null): ?FiscalYear
    {
        return FiscalYear::query()
            ->when($fiscalYearId, fn ($query, int $id) => $query->whereKey($id))
            ->when(! $fiscalYearId, fn ($query) => $query->where('status', 'active'))
            ->orderByDesc('starts_on')
            ->first();
    }

    public function paginateTraineeSummaries(FiscalYear $fiscalYear, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $fiscalYearId = $fiscalYear->id;

        return User::query()
            ->select('users.*')
            ->role('trainee')
            ->where('status', 'active')
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($traineeQuery) use ($search): void {
                    $traineeQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->withCount([
                'creditAwards as course_award_count' => fn ($query) => $query
                    ->where('fiscal_year_id', $fiscalYearId)
                    ->where('source_type', CreditSourceType::CourseCompletion->value),
                'creditAwards as quiz_award_count' => fn ($query) => $query
                    ->where('fiscal_year_id', $fiscalYearId)
                    ->where('source_type', CreditSourceType::AssessmentPass->value),
            ])
            ->withSum(['creditAwards as course_credit_points' => fn ($query) => $query
                ->where('fiscal_year_id', $fiscalYearId)
                ->where('source_type', CreditSourceType::CourseCompletion->value)], 'points')
            ->withSum(['creditAwards as quiz_credit_points' => fn ($query) => $query
                ->where('fiscal_year_id', $fiscalYearId)
                ->where('source_type', CreditSourceType::AssessmentPass->value)], 'points')
            ->withSum(['creditAwards as attendance_credit_points' => fn ($query) => $query
                ->where('fiscal_year_id', $fiscalYearId)
                ->where('source_type', CreditSourceType::Attendance->value)], 'points')
            ->withSum(['creditAwards as total_credit_points' => fn ($query) => $query
                ->where('fiscal_year_id', $fiscalYearId)], 'points')
            ->withSum(['creditAwards as claimed_credit_points' => fn ($query) => $query
                ->where('fiscal_year_id', $fiscalYearId)
                ->where('status', CreditAwardStatus::Claimed->value)], 'points')
            ->withSum(['creditAwards as ready_credit_points' => fn ($query) => $query
                ->where('fiscal_year_id', $fiscalYearId)
                ->where('status', CreditAwardStatus::Eligible->value)], 'points')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findTrainee(int $traineeId): ?User
    {
        return User::query()->role('trainee')->whereKey($traineeId)->first();
    }

    public function details(FiscalYear $fiscalYear, User $trainee): array
    {
        $startsOn = $fiscalYear->starts_on->copy()->startOfDay();
        $endsOn = $fiscalYear->ends_on->copy()->endOfDay();

        return [
            'awards' => CreditAward::query()
                ->where('fiscal_year_id', $fiscalYear->id)
                ->where('user_id', $trainee->id)
                ->with(['course', 'assessment'])
                ->latest('eligible_at')
                ->get(),
            'attendance' => AttendanceSnapshot::query()
                ->where('fiscal_year_id', $fiscalYear->id)
                ->where('user_id', $trainee->id)
                ->first(),
            'enrollments' => Enrollment::query()
                ->where('user_id', $trainee->id)
                ->whereIn('status', ['active', 'completed'])
                ->where(function ($query) use ($startsOn, $endsOn): void {
                    $query->whereBetween('enrolled_at', [$startsOn, $endsOn])
                        ->orWhereBetween('completed_at', [$startsOn, $endsOn])
                        ->orWhere(function ($overlapping) use ($startsOn, $endsOn): void {
                            $overlapping->where('enrolled_at', '<=', $startsOn)
                                ->where(function ($completion) use ($endsOn): void {
                                    $completion->whereNull('completed_at')->orWhere('completed_at', '>=', $endsOn);
                                });
                        });
                })
                ->with('course.category')
                ->orderByDesc('completed_at')
                ->orderByDesc('enrolled_at')
                ->get(),
            'assessmentAttempts' => AssessmentAttempt::query()
                ->where('user_id', $trainee->id)
                ->whereNotNull('submitted_at')
                ->whereBetween('submitted_at', [$startsOn, $endsOn])
                ->with('assessment')
                ->latest('submitted_at')
                ->get(),
            'courseAssessmentAttempts' => CourseAssessmentAttempt::query()
                ->where('user_id', $trainee->id)
                ->whereNotNull('submitted_at')
                ->whereBetween('submitted_at', [$startsOn, $endsOn])
                ->with('courseAssessment.material.chapter.module.course')
                ->latest('submitted_at')
                ->get(),
        ];
    }
}
