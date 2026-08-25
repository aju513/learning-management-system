<?php

namespace App\Services;

use App\Enums\CreditAwardStatus;
use App\Enums\CreditSourceType;
use App\Models\FiscalYear;
use App\Models\User;
use App\Repositories\Contracts\CreditScoreViewerRepositoryInterface;

class CreditScoreViewerService
{
    public function __construct(private readonly CreditScoreViewerRepositoryInterface $viewer) {}

    public function pageData(array $filters): array
    {
        $fiscalYear = $this->viewer->findFiscalYear($filters['fiscal_year_id'] ?? null);
        $selectedTrainee = null;
        $details = null;

        if ($fiscalYear && isset($filters['trainee_id'])) {
            $selectedTrainee = $this->viewer->findTrainee((int) $filters['trainee_id']);
            if ($selectedTrainee) {
                $details = $this->formatDetails($fiscalYear, $selectedTrainee);
            }
        }

        return [
            'fiscalYears' => $this->viewer->fiscalYears(),
            'fiscalYear' => $fiscalYear,
            'trainees' => $fiscalYear ? $this->viewer->paginateTraineeSummaries($fiscalYear, $filters) : null,
            'selectedTrainee' => $selectedTrainee,
            'details' => $details,
            'activeTab' => $filters['tab'] ?? 'overall',
        ];
    }

    private function formatDetails(FiscalYear $fiscalYear, User $trainee): array
    {
        $raw = $this->viewer->details($fiscalYear, $trainee);
        $awards = collect($raw['awards']);
        $courseAwards = $awards->where('source_type', CreditSourceType::CourseCompletion)->values();
        $quizAwards = $awards->where('source_type', CreditSourceType::AssessmentPass)->values();
        $enrollments = collect($raw['enrollments']);

        $courseRows = $enrollments->map(function ($enrollment) use ($courseAwards): array {
            $award = $courseAwards->firstWhere('course_id', $enrollment->course_id);

            return [
                'course' => $enrollment->course,
                'enrollment' => $enrollment,
                'award' => $award,
            ];
        });

        foreach ($courseAwards->whereNotIn('course_id', $enrollments->pluck('course_id')) as $award) {
            $courseRows->push(['course' => $award->course, 'enrollment' => null, 'award' => $award]);
        }

        $awardByKey = $quizAwards->keyBy('source_key');
        $quizRows = collect();

        foreach ($raw['assessmentAttempts'] as $attempt) {
            $quizRows->push([
                'kind' => 'Standalone quiz',
                'title' => $attempt->assessment?->title ?? 'Quiz',
                'course' => null,
                'attempt' => $attempt,
                'award' => $awardByKey->get('assessment:'.$attempt->assessment_id),
                'date' => $attempt->submitted_at,
            ]);
        }

        foreach ($raw['courseAssessmentAttempts'] as $attempt) {
            $courseAssessment = $attempt->courseAssessment;
            $material = $courseAssessment?->material;
            $course = $material?->chapter?->module?->course;
            $quizRows->push([
                'kind' => 'Course quiz',
                'title' => $material?->title ?? 'Course quiz',
                'course' => $course,
                'attempt' => $attempt,
                'award' => $awardByKey->get('course-assessment:'.$attempt->course_assessment_id),
                'date' => $attempt->submitted_at,
            ]);
        }

        $attemptKeys = $quizRows->map(fn (array $row): ?string => $row['award']?->source_key)->filter();
        foreach ($quizAwards->whereNotIn('source_key', $attemptKeys) as $award) {
            $quizRows->push([
                'kind' => 'Quiz',
                'title' => $award->source_label,
                'course' => $award->course,
                'attempt' => null,
                'award' => $award,
                'date' => $award->eligible_at,
            ]);
        }

        return [
            'overall' => [
                'total' => (float) $awards->sum('points'),
                'claimed' => (float) $awards->where('status', CreditAwardStatus::Claimed)->sum('points'),
                'ready' => (float) $awards->where('status', CreditAwardStatus::Eligible)->sum('points'),
                'course' => (float) $courseAwards->sum('points'),
                'quiz' => (float) $quizAwards->sum('points'),
                'attendance' => (float) $awards->where('source_type', CreditSourceType::Attendance)->sum('points'),
            ],
            'attendance' => $raw['attendance'],
            'awards' => $awards,
            'courses' => $courseRows->values(),
            'quizzes' => $quizRows->sortByDesc(fn (array $row) => $row['date']?->getTimestamp() ?? 0)->values(),
        ];
    }
}
