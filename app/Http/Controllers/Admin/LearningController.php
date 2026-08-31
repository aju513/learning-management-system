<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\CompleteLearningMaterialRequest;
use App\Http\Requests\Learning\DownloadLearningMaterialRequest;
use App\Http\Requests\Learning\IndexLearningRequest;
use App\Http\Requests\Learning\LaunchCourseRequest;
use App\Http\Requests\Learning\ShowLearningMaterialRequest;
use App\Http\Requests\Learning\ShowLearningSummaryRequest;
use App\Models\Enrollment;
use App\Models\LearningMaterial;
use App\Services\LearningService;
use App\Services\Training\TrainingAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LearningController extends Controller
{
    public function __construct(
        private readonly LearningService $service,
        private readonly TrainingAvailabilityService $availability,
    ) {}

    public function index(IndexLearningRequest $request): View
    {
        $learningData = $this->service->indexData(
            $request->user(),
            $this->availability->eligibleTrainingKeys($request->user()),
            $request->validated(),
        );

        return view('pages.admin.learning.index', $learningData + [
            'filters' => $request->validated(),
            'title' => 'Enrolled Courses',
        ]);
    }

    public function player(LaunchCourseRequest $request, Enrollment $enrollment): View|RedirectResponse
    {
        $result = $this->service->launch($enrollment, $request->user());

        if ($result['summary'] ?? false) {
            return redirect()->route('learning.courses.summary', $enrollment);
        }

        return view('pages.admin.learning.player', $result + ['title' => $enrollment->course->title]);
    }

    public function summary(ShowLearningSummaryRequest $request, Enrollment $enrollment): View|RedirectResponse
    {
        $summary = $this->service->summary($enrollment, $request->user());

        if ($summary['redirectToPlayer']) {
            return redirect()->route('learning.courses.player', $enrollment);
        }

        return view('pages.admin.learning.summary', $summary + ['title' => $enrollment->course->title]);
    }

    public function show(ShowLearningMaterialRequest $request, Enrollment $enrollment, LearningMaterial $learningMaterial): View
    {
        return view('pages.admin.learning.player', $this->service->open($enrollment, $learningMaterial, $request->user()) + ['title' => $learningMaterial->title]);
    }

    public function complete(CompleteLearningMaterialRequest $request, Enrollment $enrollment, LearningMaterial $learningMaterial): RedirectResponse|JsonResponse
    {
        $result = $this->service->complete($enrollment, $learningMaterial, $request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'completed' => true,
                'progress_percentage' => (float) $result['enrollment']->progress_percentage,
                'status' => $result['enrollment']->status->value,
                'course_completed' => $result['enrollment']->status->value === 'completed',
                'summary_url' => route('learning.courses.summary', $enrollment),
            ]);
        }

        if ($result['enrollment']->status->value === 'completed') {
            return redirect()->route('learning.courses.summary', $enrollment)
                ->with('success', 'Congratulations! You completed the course.');
        }

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

    public function stream(ShowLearningMaterialRequest $request, Enrollment $enrollment, LearningMaterial $learningMaterial): BinaryFileResponse
    {
        return $this->service->streamVideo($enrollment, $learningMaterial, $request->user());
    }
}
