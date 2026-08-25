<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseAssessment\ShowAttemptRequest;
use App\Http\Requests\CourseAssessment\StartCourseAssessmentRequest;
use App\Http\Requests\CourseAssessment\SubmitAttemptRequest;
use App\Models\CourseAssessmentAttempt;
use App\Models\Enrollment;
use App\Models\LearningMaterial;
use App\Repositories\Contracts\CourseAssessmentRepositoryInterface;
use App\Services\CourseAssessmentService;
use App\Services\CreditScoreService;
use App\Services\LearningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CourseAssessmentPlayerController extends Controller
{
    public function __construct(
        private readonly CourseAssessmentRepositoryInterface $courseAssessments,
        private readonly CourseAssessmentService $service,
        private readonly CreditScoreService $credits,
        private readonly LearningService $learning,
    ) {}

    public function start(StartCourseAssessmentRequest $request, Enrollment $enrollment, LearningMaterial $learningMaterial): RedirectResponse
    {
        $learningMaterial->loadMissing('courseAssessment');
        $attempt = $this->service->start($learningMaterial->courseAssessment, $enrollment, $request->user());

        return redirect()->route('learning.course-assessment-attempts.show', [$enrollment, $attempt]);
    }

    public function show(ShowAttemptRequest $request, Enrollment $enrollment, CourseAssessmentAttempt $courseAssessmentAttempt): View
    {
        $attempt = $this->courseAssessments->findAttemptForTaking($courseAssessmentAttempt);
        $course = $enrollment->course;
        $view = $attempt->status === 'in_progress'
            ? 'pages.admin.course-assessment-player.take'
            : 'pages.admin.course-assessment-player.result';
        $learningContext = $this->learning->open(
            $enrollment,
            $attempt->courseAssessment->material,
            $request->user(),
        );

        return view($view, $learningContext + [
            'attempt' => $attempt,
            'creditAward' => $attempt->passed ? $this->credits->courseAward($course, $request->user()) : null,
            'courseCreditPoints' => (float) $course->credit_points,
            'title' => $attempt->courseAssessment->material->title,
        ]);
    }

    public function submit(SubmitAttemptRequest $request, Enrollment $enrollment, CourseAssessmentAttempt $courseAssessmentAttempt): RedirectResponse|JsonResponse
    {
        $attempt = $this->service->submit($courseAssessmentAttempt, $request->validated('answers', []), $enrollment, $request->user());
        $redirect = route('learning.course-assessment-attempts.show', [$enrollment, $attempt]);

        if ($attempt->passed) {
            $continuation = $this->learning->launch($enrollment, $request->user());
            if ($continuation['summary'] ?? false) {
                $redirect = route('learning.courses.summary', $enrollment);
            } elseif ($continuation['material'] ?? null) {
                $redirect = route('learning.courses.materials.show', [$continuation['enrollment'], $continuation['material']]);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['submitted' => true, 'redirect' => $redirect]);
        }

        return redirect()->to($redirect)
            ->with('success', 'Course assessment submitted.');
    }

    public function save(SubmitAttemptRequest $request, Enrollment $enrollment, CourseAssessmentAttempt $courseAssessmentAttempt): \Illuminate\Http\JsonResponse
    {
        $this->service->saveAnswers($courseAssessmentAttempt, $request->validated('answers', []), $request->user());

        return response()->json(['saved' => true]);
    }
}
