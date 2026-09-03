<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Assessment\IndexAssessmentPlayerRequest;
use App\Http\Requests\Assessment\ShowAttemptRequest;
use App\Http\Requests\Assessment\ShowTraineeTestRequest;
use App\Http\Requests\Assessment\SubmitAttemptRequest;
use App\Http\Requests\Assessment\TakeAssessmentRequest;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Services\AssessmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AssessmentPlayerController extends Controller
{
    public function __construct(
        private readonly AssessmentService $service,
    ) {}

    public function index(IndexAssessmentPlayerRequest $request): View
    {
        $data = $this->service->traineeAssessmentIndex($request->user(), $request->validated());

        return view('pages.admin.assessment-player.index', [
            ...$data,
            'title' => 'Tests & Assessments',
            'legacyTitle' => 'Enrolled Tests',
            'description' => 'All tests assigned to you, including tests waiting to be started.',
            'emptyTitle' => 'No enrolled tests yet',
            'emptyDescription' => 'Tests assigned directly to you will appear here.',
            'showFilters' => true,
            'filters' => $request->validated(),
        ]);
    }

    public function applied(IndexAssessmentPlayerRequest $request): View
    {
        return view('pages.admin.assessment-player.index', $this->service->traineeAppliedIndex($request->user()) + [
            'title' => 'Applied Tests',
            'legacyTitle' => null,
            'description' => 'Tests assigned to you that are ready to begin.',
            'emptyTitle' => 'No applied tests yet',
            'emptyDescription' => 'Tests you are approved to take will appear here until you start an attempt.',
            'showFilters' => false,
            'filters' => [],
        ]);
    }

    public function overview(ShowTraineeTestRequest $request, Assessment $assessment): View
    {
        return view('pages.admin.assessment-player.overview', $this->service->traineeAssessmentShow($assessment, $request->user()) + [
            'title' => $assessment->title,
        ]);
    }

    public function start(TakeAssessmentRequest $request, Assessment $assessment): RedirectResponse
    {
        $attempt = $this->service->start($assessment, $request->user());

        return redirect()->route('learning.assessments.attempts.show', $attempt);
    }

    public function show(ShowAttemptRequest $request, AssessmentAttempt $assessmentAttempt): View
    {
        $data = $this->service->attemptForDisplay($assessmentAttempt, $request->user());
        $attempt = $data['attempt'];
        if ($attempt->status->value === 'pending_review') {
            return view('pages.admin.assessment-player.submitted', $data + ['title' => 'Quiz Submitted']);
        }
        if ($attempt->status->value === 'graded') {
            if (! $attempt->assessment->show_results && ! $request->user()->can('results.view-all') && ! $request->user()->can('results.view-owned')) {
                return view('pages.admin.assessment-player.submitted', $data + ['title' => 'Assessment Submitted']);
            }

            return view('pages.admin.assessment-player.result', $data + ['title' => 'Assessment Result']);
        }

        return view('pages.admin.assessment-player.take', $data + ['title' => $attempt->assessment->title]);
    }

    public function submit(SubmitAttemptRequest $request, AssessmentAttempt $assessmentAttempt): RedirectResponse|JsonResponse
    {
        $attempt = $this->service->submit($assessmentAttempt, $request->validated('answers', []), $request->user());
        $redirect = route('learning.assessments.attempts.show', $attempt);

        if ($request->expectsJson()) {
            return response()->json(['submitted' => true, 'redirect' => $redirect]);
        }

        return redirect()->to($redirect)->with('success', 'Quiz submitted successfully.');
    }

    public function save(SubmitAttemptRequest $request, AssessmentAttempt $assessmentAttempt): \Illuminate\Http\JsonResponse
    {
        $this->service->saveAnswers($assessmentAttempt, $request->validated('answers', []), $request->user());

        return response()->json(['saved' => true]);
    }
}
