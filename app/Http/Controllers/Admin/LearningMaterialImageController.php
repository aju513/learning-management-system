<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\ShowLearningMaterialImageRequest;
use App\Http\Requests\LearningMaterial\StoreLearningMaterialImageRequest;
use App\Http\Requests\LearningMaterial\UpdateLearningMaterialImageRequest;
use App\Models\CourseChapter;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialImage;
use App\Services\LearningMaterialImageService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class LearningMaterialImageController extends Controller
{
    public function __construct(private readonly LearningMaterialImageService $service) {}

    public function storeForChapter(StoreLearningMaterialImageRequest $request, CourseChapter $courseChapter): JsonResponse
    {
        return $this->uploadedResponse($this->service->upload($courseChapter, $request->file('image'), $request->user()));
    }

    public function storeForMaterial(UpdateLearningMaterialImageRequest $request, LearningMaterial $learningMaterial): JsonResponse
    {
        return $this->uploadedResponse($this->service->upload($learningMaterial->chapter, $request->file('image'), $request->user(), $learningMaterial));
    }

    public function show(ShowLearningMaterialImageRequest $request, LearningMaterialImage $learningMaterialImage): Response
    {
        return $this->service->response($learningMaterialImage);
    }

    private function uploadedResponse(LearningMaterialImage $image): JsonResponse
    {
        return response()->json([
            'id' => $image->uuid,
            'url' => route('learning-material-images.show', $image),
        ]);
    }
}
