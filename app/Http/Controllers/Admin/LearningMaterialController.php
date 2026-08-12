<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LearningMaterial\DeleteLearningMaterialRequest;
use App\Http\Requests\LearningMaterial\MoveLearningMaterialRequest;
use App\Http\Requests\LearningMaterial\StoreLearningMaterialRequest;
use App\Http\Requests\LearningMaterial\UpdateLearningMaterialRequest;
use App\Models\CourseModule;
use App\Models\LearningMaterial;
use App\Services\CourseService;
use Illuminate\Http\RedirectResponse;

class LearningMaterialController extends Controller
{
    public function __construct(private readonly CourseService $service) {}

    public function store(StoreLearningMaterialRequest $request, CourseModule $courseModule): RedirectResponse
    {
        $this->service->createMaterial($courseModule, $request->validated(), $request->user());

        return back()->with('success', 'Learning material added.');
    }

    public function update(UpdateLearningMaterialRequest $request, LearningMaterial $learningMaterial): RedirectResponse
    {
        $this->service->updateMaterial($learningMaterial, $request->validated(), $request->user());

        return back()->with('success', 'Learning material updated.');
    }

    public function move(MoveLearningMaterialRequest $request, LearningMaterial $learningMaterial): RedirectResponse
    {
        $this->service->moveMaterial($learningMaterial, $request->validated('direction'), $request->user());

        return back()->with('success', 'Learning material reordered.');
    }

    public function destroy(DeleteLearningMaterialRequest $request, LearningMaterial $learningMaterial): RedirectResponse
    {
        $this->service->deleteMaterial($learningMaterial, $request->user());

        return back()->with('success', 'Learning material deleted.');
    }
}
