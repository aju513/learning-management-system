<?php

namespace App\Repositories\Eloquent;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Repositories\Contracts\ReportRepositoryInterface;

class ReportRepository implements ReportRepositoryInterface
{
    public function superAdminDashboard(): array
    {
        return [
            'context' => 'System-wide LMS governance and oversight',
            'metrics' => [
                'Admins' => User::role('admin')->count(),
                'Instructors' => User::role('instructor')->count(),
                'Trainees' => User::role('trainee')->count(),
                'Total courses' => Course::count(),
                'Published courses' => Course::where('status', 'published')->count(),
                'Pending applications' => Enrollment::where('status', 'pending')->count(),
                'Active enrollments' => Enrollment::where('status', 'active')->count(),
                'Completed courses' => Enrollment::where('status', 'completed')->count(),
            ],
            'courses' => Course::with('instructor')->withCount('enrollments')->latest()->limit(5)->get(),
            'results' => AssessmentAttempt::where('status', 'graded')->with(['assessment', 'trainee'])->latest('submitted_at')->limit(5)->get(),
        ];
    }

    public function adminDashboard(): array
    {
        $graded = AssessmentAttempt::where('status', 'graded')->count();

        return [
            'context' => 'Day-to-day learning administration',
            'metrics' => [
                'Trainees' => User::role('trainee')->count(),
                'Published courses' => Course::where('status', 'published')->count(),
                'Pending applications' => Enrollment::where('status', 'pending')->count(),
                'Active enrollments' => Enrollment::where('status', 'active')->count(),
                'Completed courses' => Enrollment::where('status', 'completed')->count(),
                'Pass rate' => round(AssessmentAttempt::where('status', 'graded')->where('passed', true)->count() / max($graded, 1) * 100, 1).'%',
            ],
            'courses' => Course::where('status', 'published')->with('instructor')->withCount('enrollments')->latest('published_at')->limit(5)->get(),
            'results' => AssessmentAttempt::where('status', 'graded')->with(['assessment', 'trainee'])->latest('submitted_at')->limit(5)->get(),
        ];
    }

    public function instructorDashboard(User $instructor): array
    {
        $courseIds = Course::where('instructor_id', $instructor->id)->pluck('id');

        return [
            'context' => 'Your courses, learners, and assessments',
            'metrics' => [
                'My courses' => $courseIds->count(),
                'Published courses' => Course::whereIn('id', $courseIds)->where('status', 'published')->count(),
                'Pending applications' => Enrollment::whereIn('course_id', $courseIds)->where('status', 'pending')->count(),
                'Enrolled trainees' => Enrollment::whereIn('course_id', $courseIds)->whereIn('status', ['active', 'completed'])->distinct('user_id')->count('user_id'),
                'Average completion' => round((float) Enrollment::whereIn('course_id', $courseIds)->whereIn('status', ['active', 'completed'])->avg('progress_percentage'), 1).'%',
                'Active tests' => Assessment::where('created_by', $instructor->id)->where('status', 'published')->count(),
            ],
            'courses' => Course::whereIn('id', $courseIds)->withCount('enrollments')->latest()->limit(5)->get(),
            'results' => AssessmentAttempt::whereHas('assessment', fn ($query) => $query->where('created_by', $instructor->id))->where('status', 'graded')->with(['assessment', 'trainee'])->latest('submitted_at')->limit(5)->get(),
        ];
    }

    public function traineeDashboard(User $trainee): array
    {
        return [
            'context' => 'Your learning, applications, and results',
            'metrics' => [
                'Enrolled courses' => Enrollment::where('user_id', $trainee->id)->whereIn('status', ['active', 'completed'])->count(),
                'Applications pending' => Enrollment::where('user_id', $trainee->id)->where('status', 'pending')->count(),
                'In progress' => Enrollment::where('user_id', $trainee->id)->where('status', 'active')->where('progress_percentage', '>', 0)->count(),
                'Completed courses' => Enrollment::where('user_id', $trainee->id)->where('status', 'completed')->count(),
                'Passed tests' => AssessmentAttempt::where('user_id', $trainee->id)->where('passed', true)->count(),
            ],
            'courses' => Course::whereHas('enrollments', fn ($query) => $query->where('user_id', $trainee->id)->whereIn('status', ['active', 'completed']))
                ->with(['enrollments' => fn ($query) => $query->where('user_id', $trainee->id)])->latest()->limit(5)->get(),
            'results' => AssessmentAttempt::where('user_id', $trainee->id)->where('status', 'graded')->whereHas('assessment', fn ($query) => $query->where('show_results', true))->with('assessment')->latest('submitted_at')->limit(5)->get(),
        ];
    }

    public function reports(): array
    {
        $courseReports = $this->courseReports();
        $testReports = $this->testReports();

        return [
            ...$courseReports,
            ...$testReports,
            'trainees' => User::query()->permission('learning.view')->select(['users.id', 'users.name', 'users.email'])
                ->withCount(['enrollments', 'enrollments as completed_count' => fn ($query) => $query->where('status', 'completed')])
                ->orderBy('name')->get(),
            'summary' => [
                'completion_rate' => round((float) (Enrollment::where('status', 'completed')->count() / max(Enrollment::count(), 1) * 100), 1),
                'pass_rate' => round((float) (AssessmentAttempt::where('passed', true)->count() / max(AssessmentAttempt::where('status', 'graded')->count(), 1) * 100), 1),
                'average_progress' => round((float) Enrollment::avg('progress_percentage'), 1),
            ],
        ];
    }

    public function courseReports(): array
    {
        return [
            'courses' => Course::query()->select(['courses.id', 'courses.title', 'courses.status'])
                ->withCount(['enrollments', 'enrollments as completed_count' => fn ($query) => $query->where('status', 'completed')])
                ->orderBy('title')->get(),
        ];
    }

    public function testReports(): array
    {
        return [
            'assessments' => Assessment::query()->select(['assessments.id', 'assessments.title'])
                ->withCount([
                    'attempts as attempts_count' => fn ($query) => $query->where('status', 'graded'),
                    'attempts as pass_count' => fn ($query) => $query->where('status', 'graded')->where('passed', true),
                    'attempts as fail_count' => fn ($query) => $query->where('status', 'graded')->where('passed', false),
                ])->addSelect(['average_score' => AssessmentAttempt::query()->selectRaw('ROUND(AVG(score_percentage), 2)')->whereColumn('assessment_id', 'assessments.id')->where('status', 'graded')])
                ->orderBy('title')->get(),
        ];
    }
}
