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
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AssessmentPlayerController extends Controller
{
    public function __construct(private readonly AssessmentRepositoryInterface $assessments, private readonly AssessmentService $service) {}

    public function index(IndexAssessmentPlayerRequest $request): View
    {
        return view('pages.admin.assessment-player.index', ['assessments' => $this->assessments->availableFor($request->user()), 'title' => 'My Tests']);
    }

    public function start(TakeAssessmentRequest $request, Assessment $assessment): RedirectResponse
    {
        $attempt = $this->service->start($assessment, $request->user());

        return redirect()->route('learning.assessments.attempts.show', $attempt);
    }

    public function show(ShowAttemptRequest $request, AssessmentAttempt $assessmentAttempt): View
    {
        $attempt = $this->assessments->findAttemptForTaking($assessmentAttempt);
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

        return redirect()->route('learning.assessments.attempts.show', $assessmentAttempt)->with('success', 'Assessment submitted and graded.');
    }
}
