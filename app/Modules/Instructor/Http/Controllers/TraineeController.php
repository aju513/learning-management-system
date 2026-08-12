<?php

namespace App\Modules\Instructor\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Enrollment\IndexOwnedCourseProgressRequest;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Repositories\Contracts\EnrollmentRepositoryInterface;
use Illuminate\View\View;

class TraineeController extends Controller
{
    public function __construct(
        private readonly EnrollmentRepositoryInterface $enrollments,
        private readonly CourseRepositoryInterface $courses,
    ) {}

    public function index(IndexOwnedCourseProgressRequest $request): View
    {
        return view('modules.instructor.trainees.index', [
            'enrollments' => $this->enrollments->paginateOwnedCourseProgress($request->validated(), $request->user()),
            'courses' => $this->courses->coursesForAuthoring($request->user()),
            'title' => 'My Trainees',
        ]);
    }
}
