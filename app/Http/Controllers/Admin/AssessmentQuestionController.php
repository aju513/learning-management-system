<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Assessment\DeleteQuestionRequest;
use App\Http\Requests\Assessment\EditQuestionRequest;
use App\Http\Requests\Assessment\StoreQuestionRequest;
use App\Http\Requests\Assessment\UpdateQuestionRequest;
use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use App\Repositories\Contracts\AssessmentRepositoryInterface;
use App\Services\AssessmentService;
use App\Support\PortalRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AssessmentQuestionController extends Controller
{
    public function __construct(private readonly AssessmentService $service, private readonly AssessmentRepositoryInterface $assessments) {}

    public function store(StoreQuestionRequest $request, Assessment $assessment): RedirectResponse
    {
        $this->service->createQuestion($assessment, $request->validated(), $request->user());

        return back()->with('success', 'Question added.');
    }

    public function edit(EditQuestionRequest $request, AssessmentQuestion $assessmentQuestion): View
    {
        return view('pages.admin.assessments.question-edit', ['question' => $this->assessments->findQuestionForEdit($assessmentQuestion), 'title' => 'Edit Question']);
    }

    public function update(UpdateQuestionRequest $request, AssessmentQuestion $assessmentQuestion): RedirectResponse
    {
        $this->service->updateQuestion($assessmentQuestion, $request->validated(), $request->user());

        return redirect()->route(PortalRoute::name('assessments.show'), $assessmentQuestion->assessment_id)->with('success', 'Question updated.');
    }

    public function destroy(DeleteQuestionRequest $request, AssessmentQuestion $assessmentQuestion): RedirectResponse
    {
        $this->service->deleteQuestion($assessmentQuestion, $request->user());

        return back()->with('success', 'Question deleted.');
    }
}
