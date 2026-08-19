<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CourseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Course\ChangeCourseStatusRequest;
use App\Http\Requests\Course\DeleteCourseRequest;
use App\Http\Requests\Course\EditCourseRequest;
use App\Http\Requests\Course\IndexCourseRequest;
use App\Http\Requests\Course\ShowCourseRequest;
use App\Http\Requests\Course\StoreCourseRequest;
use App\Http\Requests\Course\UpdateCourseRequest;
use App\Models\Course;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Services\CourseService;
use App\Support\PortalRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function __construct(
        private readonly CourseRepositoryInterface $courses,
        private readonly CourseService $service,
    ) {}

    public function index(IndexCourseRequest $request): View
    {
        return view('pages.admin.courses.index', [
            'courses' => $this->courses->paginateCourses($request->validated(), $request->user()),
            'categories' => $this->courses->activeCategories(), 'title' => 'Courses',
        ]);
    }

    public function create(): View
    {
        return view('pages.admin.courses.create', $this->formData(new Course, request()->user()) + ['title' => 'Create Course']);
    }

    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $course = $this->service->createCourse($request->validated(), $request->user());

        return redirect()->route(PortalRoute::name('courses.show'), $course)->with('success', 'Course created. Build its curriculum below.');
    }

    public function show(ShowCourseRequest $request, Course $course): View
    {
        return view('pages.admin.courses.show', [
            'course' => $this->courses->findCourseDetails($course), 'title' => $course->title,
        ]);
    }

    public function edit(EditCourseRequest $request, Course $course): View
    {
        return view('pages.admin.courses.edit', $this->formData($course, $request->user()) + ['title' => 'Edit Course']);
    }

    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $this->service->updateCourse($course, $request->validated(), $request->user());

        return redirect()->route(PortalRoute::name('courses.show'), $course)->with('success', 'Course updated.');
    }

    public function status(ChangeCourseStatusRequest $request, Course $course): RedirectResponse
    {
        $this->service->changeStatus($course, CourseStatus::from($request->validated('status')), $request->user());

        return back()->with('success', 'Course status updated.');
    }

    public function destroy(DeleteCourseRequest $request, Course $course): RedirectResponse
    {
        $this->service->deleteCourse($course, $request->user());

        return redirect()->route(PortalRoute::name('courses.index'))->with('success', 'Course deleted.');
    }

    private function formData(Course $course, \App\Models\User $actor): array
    {
        return ['course' => $course, 'categories' => $this->courses->activeCategories(), 'instructors' => $this->courses->instructors(), 'actor' => $actor];
    }
}
