<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AssessmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Assessment\ChangeAssessmentStatusRequest;
use App\Http\Requests\Assessment\DeleteAssessmentRequest;
use App\Http\Requests\Assessment\EditAssessmentRequest;
use App\Http\Requests\Assessment\IndexAssessmentRequest;
use App\Http\Requests\Assessment\ShowAssessmentRequest;
use App\Http\Requests\Assessment\StoreAssessmentRequest;
use App\Http\Requests\Assessment\UpdateAssessmentRequest;
use App\Models\Assessment;
use App\Repositories\Contracts\AssessmentRepositoryInterface;
use App\Repositories\Contracts\EnrollmentRepositoryInterface;
use App\Services\AssessmentService;
use App\Services\Training\TrainingCatalogProviderInterface;
use App\Support\PortalRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function __construct(
        private readonly AssessmentRepositoryInterface $assessments,
        private readonly EnrollmentRepositoryInterface $enrollments,
        private readonly AssessmentService $service,
        private readonly TrainingCatalogProviderInterface $trainingCatalog,
    ) {}

    public function index(IndexAssessmentRequest $request): View
    {
        return view('pages.admin.assessments.index', ['assessments' => $this->assessments->paginate($request->validated(), $request->user()), 'title' => 'Tests & Quizzes']);
    }

    public function create(): View
    {
        return view('pages.admin.assessments.create', [
            'assessment' => new Assessment(['show_results' => true]),
            'trainings' => $this->trainingCatalog->all(),
            'title' => 'Create Quiz',
        ]);
    }

    public function store(StoreAssessmentRequest $request): RedirectResponse
    {
        $assessment = $this->service->create($request->validated(), $request->user());

        return redirect()->route(PortalRoute::name('assessments.show'), $assessment)->with('success', 'Assessment created. Add questions below.');
    }

    public function show(ShowAssessmentRequest $request, Assessment $assessment): View
    {
        return view('pages.admin.assessments.show', ['assessment' => $this->assessments->findForManagement($assessment), 'trainees' => $this->enrollments->traineesForAssessmentAssignment($request->user()), 'title' => $assessment->title, 'activeTab' => $request->string('tab')->value() ?: 'questions']);
    }

    public function edit(EditAssessmentRequest $request, Assessment $assessment): View
    {
        return view('pages.admin.assessments.edit', [
            'assessment' => $this->assessments->findForManagement($assessment),
            'trainings' => $this->trainingCatalog->all(),
            'title' => 'Edit Quiz',
        ]);
    }

    public function update(UpdateAssessmentRequest $request, Assessment $assessment): RedirectResponse
    {
        $this->service->update($assessment, $request->validated(), $request->user());

        return redirect()->route(PortalRoute::name('assessments.show'), $assessment)->with('success', 'Assessment updated.');
    }

    public function status(ChangeAssessmentStatusRequest $request, Assessment $assessment): RedirectResponse
    {
        $this->service->changeStatus($assessment, AssessmentStatus::from($request->validated('status')), $request->user());

        return back()->with('success', 'Assessment status updated.');
    }

    public function destroy(DeleteAssessmentRequest $request, Assessment $assessment): RedirectResponse
    {
        $this->service->delete($assessment, $request->user());

        return redirect()->route(PortalRoute::name('assessments.index'))->with('success', 'Assessment deleted.');
    }
}
