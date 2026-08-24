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
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CourseAssessmentPlayerController extends Controller
{
    public function __construct(
        private readonly CourseAssessmentRepositoryInterface $courseAssessments,
        private readonly CourseAssessmentService $service,
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
        $view = $attempt->status === 'in_progress'
            ? 'pages.admin.course-assessment-player.take'
            : 'pages.admin.course-assessment-player.result';

        return view($view, ['attempt' => $attempt, 'enrollment' => $enrollment, 'title' => $attempt->courseAssessment->material->title]);
    }

    public function submit(SubmitAttemptRequest $request, Enrollment $enrollment, CourseAssessmentAttempt $courseAssessmentAttempt): RedirectResponse
    {
        $this->service->submit($courseAssessmentAttempt, $request->validated('answers', []), $enrollment, $request->user());

        return redirect()->route('learning.course-assessment-attempts.show', [$enrollment, $courseAssessmentAttempt])
            ->with('success', 'Course assessment submitted.');
    }

    public function save(SubmitAttemptRequest $request, Enrollment $enrollment, CourseAssessmentAttempt $courseAssessmentAttempt): \Illuminate\Http\JsonResponse
    {
        $this->service->saveAnswers($courseAssessmentAttempt, $request->validated('answers', []), $request->user());

        return response()->json(['saved' => true]);
    }
}
