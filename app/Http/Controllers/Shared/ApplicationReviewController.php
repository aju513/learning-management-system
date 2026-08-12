<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseApplication\IndexCourseApplicationRequest;
use App\Http\Requests\CourseApplication\ReviewCourseApplicationRequest;
use App\Models\Enrollment;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Repositories\Contracts\EnrollmentRepositoryInterface;
use App\Services\CourseApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ApplicationReviewController extends Controller
{
    public function __construct(
        private readonly EnrollmentRepositoryInterface $enrollments,
        private readonly CourseRepositoryInterface $courses,
        private readonly CourseApplicationService $service,
    ) {}

    public function index(IndexCourseApplicationRequest $request): View
    {
        return view('modules.shared.applications.index', [
            'applications' => $this->enrollments->paginateApplications($request->validated(), $request->user()),
            'courses' => $this->courses->coursesForApplicationReview($request->user()),
            'routePrefix' => str($request->route()->getName())->before('.')->toString(),
            'title' => 'Course Applications',
        ]);
    }

    public function approve(ReviewCourseApplicationRequest $request, Enrollment $enrollment): RedirectResponse
    {
        $this->service->approve($enrollment, $request->user());

        return back()->with('success', 'Course application approved.');
    }

    public function reject(ReviewCourseApplicationRequest $request, Enrollment $enrollment): RedirectResponse
    {
        $this->service->reject($enrollment, $request->user(), $request->validated('review_note'));

        return back()->with('success', 'Course application rejected.');
    }
}
