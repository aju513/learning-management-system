<?php

namespace App\Modules\Trainee\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Modules\Trainee\Http\Requests\ApplyForCourseRequest;
use App\Modules\Trainee\Http\Requests\IndexOwnApplicationsRequest;
use App\Repositories\Contracts\EnrollmentRepositoryInterface;
use App\Services\CourseApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function __construct(
        private readonly EnrollmentRepositoryInterface $enrollments,
        private readonly CourseApplicationService $service,
    ) {}

    public function index(IndexOwnApplicationsRequest $request): View
    {
        return view('modules.trainee.applications.index', [
            'applications' => $this->enrollments->applicationsForTrainee($request->user()),
            'title' => 'Applied Courses',
        ]);
    }

    public function store(ApplyForCourseRequest $request, Course $course): RedirectResponse
    {
        $this->service->apply($course, $request->user());

        return redirect()->route('learning.applications.index')->with('success', 'Your course application was submitted for review.');
    }
}
