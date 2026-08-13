<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MaterialType;
use App\Http\Controllers\Controller;
use App\Http\Requests\LearningMaterial\CreateLearningMaterialRequest;
use App\Http\Requests\LearningMaterial\DeleteLearningMaterialRequest;
use App\Http\Requests\LearningMaterial\EditLearningMaterialRequest;
use App\Http\Requests\LearningMaterial\MoveLearningMaterialRequest;
use App\Http\Requests\LearningMaterial\ReorderLearningMaterialsRequest;
use App\Http\Requests\LearningMaterial\StoreLearningMaterialRequest;
use App\Http\Requests\LearningMaterial\UpdateLearningMaterialRequest;
use App\Models\CourseChapter;
use App\Models\LearningMaterial;
use App\Repositories\Contracts\AssessmentRepositoryInterface;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Services\CourseService;
use App\Support\PortalRoute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LearningMaterialController extends Controller
{
    public function __construct(
        private readonly CourseRepositoryInterface $courses,
        private readonly AssessmentRepositoryInterface $assessments,
        private readonly CourseService $service,
    ) {}

    public function create(CreateLearningMaterialRequest $request, CourseChapter $courseChapter): View
    {
        $chapter = $this->courses->findChapterDetails($courseChapter);

        return view('pages.admin.learning-materials.create', $this->formData(new LearningMaterial(['type' => MaterialType::Article, 'is_required' => true, 'duration_minutes' => 0]), $chapter, $request) + ['title' => 'Add Learning Material']);
    }

    public function store(StoreLearningMaterialRequest $request, CourseChapter $courseChapter): RedirectResponse
    {
        $this->service->createMaterial($courseChapter, $request->validated(), $request->user());

        return $this->courseRedirect($courseChapter)->with('success', 'Learning material added.');
    }

    public function edit(EditLearningMaterialRequest $request, LearningMaterial $learningMaterial): View
    {
        $material = $this->courses->findMaterialDetails($learningMaterial);

        return view('pages.admin.learning-materials.edit', $this->formData($material, $material->chapter, $request) + ['title' => 'Edit Learning Material']);
    }

    public function update(UpdateLearningMaterialRequest $request, LearningMaterial $learningMaterial): RedirectResponse
    {
        $this->service->updateMaterial($learningMaterial, $request->validated(), $request->user());

        return $this->courseRedirect($learningMaterial->chapter)->with('success', 'Learning material updated.');
    }

    public function move(MoveLearningMaterialRequest $request, LearningMaterial $learningMaterial): RedirectResponse
    {
        $this->service->moveMaterial($learningMaterial, $request->validated('direction'), $request->user());

        return back()->with('success', 'Learning material reordered.');
    }

    public function reorder(ReorderLearningMaterialsRequest $request, CourseChapter $courseChapter): JsonResponse
    {
        $this->service->reorderMaterials($courseChapter, $request->validated('material_ids'), $request->user());

        return response()->json(['message' => 'Learning material order updated.']);
    }

    public function destroy(DeleteLearningMaterialRequest $request, LearningMaterial $learningMaterial): RedirectResponse
    {
        $this->service->deleteMaterial($learningMaterial, $request->user());

        return back()->with('success', 'Learning material deleted.');
    }

    private function formData(LearningMaterial $material, CourseChapter $chapter, \Illuminate\Http\Request $request): array
    {
        return [
            'material' => $material,
            'chapter' => $chapter,
            'attachableAssessments' => $this->assessments->attachable($request->user()),
        ];
    }

    private function courseRedirect(CourseChapter $chapter): RedirectResponse
    {
        return redirect()->to(route(PortalRoute::name('courses.show'), $chapter->module->course).'#chapter-'.$chapter->id);
    }
}
