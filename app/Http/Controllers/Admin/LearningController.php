<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\CompleteLearningMaterialRequest;
use App\Http\Requests\Learning\DownloadLearningMaterialRequest;
use App\Http\Requests\Learning\IndexLearningRequest;
use App\Http\Requests\Learning\LaunchCourseRequest;
use App\Http\Requests\Learning\ShowLearningMaterialRequest;
use App\Models\Enrollment;
use App\Models\LearningMaterial;
use App\Repositories\Contracts\EnrollmentRepositoryInterface;
use App\Services\LearningService;
use App\Services\Training\TrainingAvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LearningController extends Controller
{
    public function __construct(
        private readonly EnrollmentRepositoryInterface $enrollments,
        private readonly LearningService $service,
        private readonly TrainingAvailabilityService $availability,
    ) {}

    public function index(IndexLearningRequest $request): View
    {
        return view('pages.admin.learning.index', ['enrollments' => $this->enrollments->forTrainee($request->user(), $this->availability->eligibleTrainingKeys($request->user())), 'title' => 'Enrolled Courses']);
    }

    public function player(LaunchCourseRequest $request, Enrollment $enrollment): View
    {
        return view('pages.admin.learning.player', $this->service->launch($enrollment, $request->user()) + ['title' => $enrollment->course->title]);
    }

    public function show(ShowLearningMaterialRequest $request, Enrollment $enrollment, LearningMaterial $learningMaterial): View
    {
        return view('pages.admin.learning.player', $this->service->open($enrollment, $learningMaterial, $request->user()) + ['title' => $learningMaterial->title]);
    }

    public function complete(CompleteLearningMaterialRequest $request, Enrollment $enrollment, LearningMaterial $learningMaterial): RedirectResponse
    {
        $result = $this->service->complete($enrollment, $learningMaterial, $request->user());

        $redirect = back()->with('success', 'Material marked complete.');
        if ($result['creditAward']) {
            $redirect->with('credit_award_id', $result['creditAward']->id);
        }

        return $redirect;
    }

    public function download(DownloadLearningMaterialRequest $request, Enrollment $enrollment, LearningMaterial $learningMaterial): StreamedResponse
    {
        abort_unless($learningMaterial->file_path && Storage::disk('local')->exists($learningMaterial->file_path), 404);

        return Storage::disk('local')->download($learningMaterial->file_path, $learningMaterial->original_filename);
    }
}
