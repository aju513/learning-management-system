<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Assessment\IndexAssessmentPlayerRequest;
use App\Http\Requests\Assessment\ShowAttemptRequest;
use App\Http\Requests\Assessment\SubmitAttemptRequest;
use App\Http\Requests\Assessment\TakeAssessmentRequest;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Repositories\Contracts\AssessmentRepositoryInterface;
use App\Services\AssessmentService;
use App\Services\Training\TrainingAvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AssessmentPlayerController extends Controller
{
    public function __construct(
        private readonly AssessmentRepositoryInterface $assessments,
        private readonly AssessmentService $service,
        private readonly TrainingAvailabilityService $availability,
    ) {}

    public function index(IndexAssessmentPlayerRequest $request): View
    {
        $data = $this->service->traineeAssessmentIndex($request->user(), $this->availability->eligibleTrainingKeys($request->user()), $request->validated());

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
        $assessments = $this->assessments->appliedFor($request->user(), $this->availability->eligibleTrainingKeys($request->user()));

        return view('pages.admin.assessment-player.index', [
            'assessments' => $assessments,
            'title' => 'Applied Tests',
            'legacyTitle' => null,
            'description' => 'Tests assigned to you that you have not started yet.',
            'emptyTitle' => 'No applied tests yet',
            'emptyDescription' => 'Tests assigned directly to you will appear here.',
            'assessmentMeta' => $assessments->mapWithKeys(fn ($assessment) => [$assessment->id => $this->service->assessmentCardMeta($assessment)]),
            'showFilters' => false,
        ]);
    }

    public function start(TakeAssessmentRequest $request, Assessment $assessment): RedirectResponse
    {
        $attempt = $this->service->start($assessment, $request->user());

        return redirect()->route('learning.assessments.attempts.show', $attempt);
    }

    public function show(ShowAttemptRequest $request, AssessmentAttempt $assessmentAttempt): View
    {
        $attempt = $this->assessments->findAttemptForTaking($assessmentAttempt);
        if ($attempt->status->value === 'pending_review') {
            return view('pages.admin.assessment-player.submitted', ['attempt' => $attempt, 'title' => 'Quiz Submitted']);
        }
        if ($attempt->status->value === 'graded') {
            if (! $attempt->assessment->show_results && ! $request->user()->can('results.view-all') && ! $request->user()->can('results.view-owned')) {
                return view('pages.admin.assessment-player.submitted', ['attempt' => $attempt, 'title' => 'Assessment Submitted']);
            }

            return view('pages.admin.assessment-player.result', ['attempt' => $attempt, 'title' => 'Assessment Result']);
        }

        return view('pages.admin.assessment-player.take', ['attempt' => $attempt, 'title' => $attempt->assessment->title]);
    }

    public function submit(SubmitAttemptRequest $request, AssessmentAttempt $assessmentAttempt): RedirectResponse
    {
        $this->service->submit($assessmentAttempt, $request->validated('answers', []), $request->user());

        return redirect()->route('learning.assessments.attempts.show', $assessmentAttempt)->with('success', 'Quiz submitted successfully.');
    }

    public function save(SubmitAttemptRequest $request, AssessmentAttempt $assessmentAttempt): \Illuminate\Http\JsonResponse
    {
        $this->service->saveAnswers($assessmentAttempt, $request->validated('answers', []), $request->user());

        return response()->json(['saved' => true]);
    }
}
