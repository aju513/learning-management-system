<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Assessment\DeleteAssessmentAssignmentRequest;
use App\Http\Requests\Assessment\StoreAssessmentAssignmentRequest;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Services\AssessmentService;
use Illuminate\Http\RedirectResponse;

class AssessmentAssignmentController extends Controller
{
    public function __construct(private readonly AssessmentService $service) {}

    public function store(StoreAssessmentAssignmentRequest $request, Assessment $assessment): RedirectResponse
    {
        $this->service->assign($assessment, $request->validated('trainees'), $request->validated('due_at'), $request->user());

        return back()->with('success', 'Assessment assigned.');
    }

    public function destroy(DeleteAssessmentAssignmentRequest $request, AssessmentAssignment $assessmentAssignment): RedirectResponse
    {
        $this->service->unassign($assessmentAssignment, $request->user());

        return back()->with('success', 'Assessment assignment removed.');
    }
}
