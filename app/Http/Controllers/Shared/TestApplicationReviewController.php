<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssessmentApplication\IndexTestApplicationRequest;
use App\Http\Requests\AssessmentApplication\ReviewTestApplicationRequest;
use App\Models\AssessmentApplication;
use App\Services\AssessmentApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TestApplicationReviewController extends Controller
{
    public function __construct(private readonly AssessmentApplicationService $service) {}

    public function index(IndexTestApplicationRequest $request): View
    {
        return view('modules.shared.test-applications.index', $this->service->reviewIndex($request->user(), $request->validated()) + [
            'routePrefix' => str($request->route()->getName())->before('.')->toString(),
            'title' => 'Test Applications',
        ]);
    }

    public function approve(ReviewTestApplicationRequest $request, AssessmentApplication $assessmentApplication): RedirectResponse
    {
        $this->service->approve($assessmentApplication, $request->user());

        return back()->with('success', 'Test application approved.');
    }

    public function reject(ReviewTestApplicationRequest $request, AssessmentApplication $assessmentApplication): RedirectResponse
    {
        $this->service->reject($assessmentApplication, $request->user(), $request->validated('review_note'));

        return back()->with('success', 'Test application rejected.');
    }
}
