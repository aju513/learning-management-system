<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssessmentCategory\DeleteAssessmentCategoryRequest;
use App\Http\Requests\AssessmentCategory\IndexAssessmentCategoryRequest;
use App\Http\Requests\AssessmentCategory\StoreAssessmentCategoryRequest;
use App\Http\Requests\AssessmentCategory\UpdateAssessmentCategoryRequest;
use App\Models\AssessmentCategory;
use App\Repositories\Contracts\AssessmentRepositoryInterface;
use App\Services\AssessmentService;
use App\Support\PortalRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AssessmentCategoryController extends Controller
{
    public function __construct(private readonly AssessmentRepositoryInterface $assessments, private readonly AssessmentService $service) {}

    public function index(IndexAssessmentCategoryRequest $request): View
    {
        return view('pages.admin.assessment-categories.index', ['categories' => $this->assessments->paginateCategories($request->validated()), 'title' => 'Test Categories']);
    }

    public function create(): View
    {
        return view('pages.admin.assessment-categories.create', ['category' => new AssessmentCategory(['is_active' => true]), 'title' => 'Create Test Category']);
    }

    public function store(StoreAssessmentCategoryRequest $request): RedirectResponse
    {
        $this->service->createCategory($request->validated(), $request->user());

        return redirect()->route(PortalRoute::name('assessment-categories.index'))->with('success', 'Test category created.');
    }

    public function edit(AssessmentCategory $assessmentCategory): View
    {
        return view('pages.admin.assessment-categories.edit', ['category' => $assessmentCategory, 'title' => 'Edit Test Category']);
    }

    public function update(UpdateAssessmentCategoryRequest $request, AssessmentCategory $assessmentCategory): RedirectResponse
    {
        $this->service->updateCategory($assessmentCategory, $request->validated(), $request->user());

        return redirect()->route(PortalRoute::name('assessment-categories.index'))->with('success', 'Test category updated.');
    }

    public function destroy(DeleteAssessmentCategoryRequest $request, AssessmentCategory $assessmentCategory): RedirectResponse
    {
        $this->service->deleteCategory($assessmentCategory, $request->user());

        return back()->with('success', 'Test category deleted.');
    }
}
