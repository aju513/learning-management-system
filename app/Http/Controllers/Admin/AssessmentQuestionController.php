<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Assessment\DeleteQuestionRequest;
use App\Http\Requests\Assessment\DownloadQuestionTemplateRequest;
use App\Http\Requests\Assessment\EditQuestionRequest;
use App\Http\Requests\Assessment\ImportQuestionsRequest;
use App\Http\Requests\Assessment\ReorderQuestionsRequest;
use App\Http\Requests\Assessment\StoreQuestionRequest;
use App\Http\Requests\Assessment\UpdateQuestionRequest;
use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use App\Repositories\Contracts\AssessmentRepositoryInterface;
use App\Services\AssessmentImportService;
use App\Services\AssessmentService;
use App\Support\PortalRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssessmentQuestionController extends Controller
{
    public function __construct(
        private readonly AssessmentService $service,
        private readonly AssessmentImportService $importer,
        private readonly AssessmentRepositoryInterface $assessments,
    ) {}

    public function template(DownloadQuestionTemplateRequest $request): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            (new Xlsx($this->importer->template()))->save('php://output');
        }, 'quiz-question-import-template.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function import(ImportQuestionsRequest $request, Assessment $assessment): RedirectResponse
    {
        $count = $this->importer->import($assessment, $request->file('file'), $request->user());

        return back()->with('success', "{$count} questions imported.");
    }

    public function reorder(ReorderQuestionsRequest $request, Assessment $assessment): RedirectResponse
    {
        $this->service->reorderQuestions($assessment, $request->validated('question_ids'), $request->user());

        return back()->with('success', 'Question order updated.');
    }

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
