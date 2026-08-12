<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Enrollment\DeleteEnrollmentRequest;
use App\Http\Requests\Enrollment\IndexEnrollmentRequest;
use App\Http\Requests\Enrollment\StoreEnrollmentRequest;
use App\Models\Enrollment;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Repositories\Contracts\EnrollmentRepositoryInterface;
use App\Services\EnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    public function __construct(
        private readonly EnrollmentRepositoryInterface $enrollments,
        private readonly CourseRepositoryInterface $courses,
        private readonly EnrollmentService $service,
    ) {}

    public function index(IndexEnrollmentRequest $request): View
    {
        return view('pages.admin.enrollments.index', [
            'enrollments' => $this->enrollments->paginate($request->validated()), 'courses' => $this->courses->allPublishedCourses(),
            'trainees' => $this->enrollments->trainees(), 'title' => 'Enrollments',
        ]);
    }

    public function store(StoreEnrollmentRequest $request): RedirectResponse
    {
        $course = $this->courses->findCourse((int) $request->validated('course_id'));
        $this->service->assign($course, $request->validated('trainees'), $request->user());

        return back()->with('success', 'Selected trainees were enrolled.');
    }

    public function destroy(DeleteEnrollmentRequest $request, Enrollment $enrollment): RedirectResponse
    {
        $this->service->cancel($enrollment, $request->user());

        return back()->with('success', 'Enrollment cancelled.');
    }
}
