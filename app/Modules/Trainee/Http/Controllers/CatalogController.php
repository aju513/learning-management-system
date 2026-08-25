<?php

namespace App\Modules\Trainee\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Modules\Trainee\Http\Requests\IndexCatalogRequest;
use App\Modules\Trainee\Http\Requests\ShowCatalogCourseRequest;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Services\LearningService;
use App\Services\Training\TrainingAvailabilityService;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __construct(
        private readonly CourseRepositoryInterface $courses,
        private readonly TrainingAvailabilityService $availability,
        private readonly LearningService $learning,
    ) {}

    public function index(IndexCatalogRequest $request): View
    {
        return view('modules.trainee.catalog.index', [
            'courses' => $this->courses->paginatePublishedCatalog($request->validated(), $request->user(), $this->availability->eligibleTrainingKeys($request->user())),
            'categories' => $this->courses->activeCategories(),
            'title' => 'Course Catalog',
        ]);
    }

    public function show(ShowCatalogCourseRequest $request, Course $course): View
    {
        $this->availability->assertAvailable($course, $request->user());

        $course = $this->courses->findPublishedCatalogCourse($course, $request->user(), $this->availability->eligibleTrainingKeys($request->user()));
        $enrollment = $course->enrollments->first(fn ($enrollment) => $enrollment->status->grantsLearningAccess());
        if ($enrollment) {
            $enrollment->setRelation('course', $course);
        }

        return view('modules.trainee.catalog.show', [
            'course' => $course,
            'progress' => $enrollment ? $this->learning->progress($enrollment, $request->user()) : null,
            'title' => $course->title,
        ]);
    }
}
