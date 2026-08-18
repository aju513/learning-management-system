<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseAssessment\DeleteQuestionRequest;
use App\Http\Requests\CourseAssessment\ReorderQuestionsRequest;
use App\Http\Requests\CourseAssessment\ShowCourseAssessmentRequest;
use App\Http\Requests\CourseAssessment\StoreQuestionRequest;
use App\Http\Requests\CourseAssessment\UpdateQuestionRequest;
use App\Models\CourseAssessment;
use App\Models\CourseAssessmentQuestion;
use App\Repositories\Contracts\CourseAssessmentRepositoryInterface;
use App\Services\CourseAssessmentService;
use App\Support\PortalRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CourseAssessmentController extends Controller
{
    public function __construct(
        private readonly CourseAssessmentRepositoryInterface $courseAssessments,
        private readonly CourseAssessmentService $service,
    ) {}

    public function show(ShowCourseAssessmentRequest $request, CourseAssessment $courseAssessment): View
    {
        return view('pages.admin.course-assessments.show', [
            'assessment' => $this->courseAssessments->findForManagement($courseAssessment),
            'title' => 'Course Assessment Questions',
        ]);
    }

    public function store(StoreQuestionRequest $request, CourseAssessment $courseAssessment): RedirectResponse
    {
        $this->service->createQuestion($courseAssessment, $request->validated(), $request->user());

        return back()->with('success', 'Course assessment question added.');
    }

    public function edit(UpdateQuestionRequest $request, CourseAssessmentQuestion $courseAssessmentQuestion): View
    {
        return view('pages.admin.course-assessments.question-edit', [
            'question' => $this->courseAssessments->findQuestionForEdit($courseAssessmentQuestion),
            'title' => 'Edit Course Assessment Question',
        ]);
    }

    public function update(UpdateQuestionRequest $request, CourseAssessmentQuestion $courseAssessmentQuestion): RedirectResponse
    {
        $this->service->updateQuestion($courseAssessmentQuestion, $request->validated(), $request->user());

        return redirect()->route(PortalRoute::name('course-assessments.show'), $courseAssessmentQuestion->course_assessment_id)
            ->with('success', 'Course assessment question updated.');
    }

    public function destroy(DeleteQuestionRequest $request, CourseAssessmentQuestion $courseAssessmentQuestion): RedirectResponse
    {
        $this->service->deleteQuestion($courseAssessmentQuestion, $request->user());

        return back()->with('success', 'Course assessment question deleted.');
    }

    public function reorder(ReorderQuestionsRequest $request, CourseAssessment $courseAssessment): RedirectResponse
    {
        $this->service->reorderQuestions($courseAssessment, $request->validated('question_ids'), $request->user());

        return back()->with('success', 'Course assessment question order updated.');
    }
}
