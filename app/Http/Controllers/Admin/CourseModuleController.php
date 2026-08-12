<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseModule\DeleteCourseModuleRequest;
use App\Http\Requests\CourseModule\MoveCourseModuleRequest;
use App\Http\Requests\CourseModule\StoreCourseModuleRequest;
use App\Http\Requests\CourseModule\UpdateCourseModuleRequest;
use App\Models\Course;
use App\Models\CourseModule;
use App\Services\CourseService;
use Illuminate\Http\RedirectResponse;

class CourseModuleController extends Controller
{
    public function __construct(private readonly CourseService $service) {}

    public function store(StoreCourseModuleRequest $request, Course $course): RedirectResponse
    {
        $this->service->createModule($course, $request->validated(), $request->user());

        return back()->with('success', 'Module added.');
    }

    public function update(UpdateCourseModuleRequest $request, CourseModule $courseModule): RedirectResponse
    {
        $this->service->updateModule($courseModule, $request->validated(), $request->user());

        return back()->with('success', 'Module updated.');
    }

    public function move(MoveCourseModuleRequest $request, CourseModule $courseModule): RedirectResponse
    {
        $this->service->moveModule($courseModule, $request->validated('direction'), $request->user());

        return back()->with('success', 'Module reordered.');
    }

    public function destroy(DeleteCourseModuleRequest $request, CourseModule $courseModule): RedirectResponse
    {
        $this->service->deleteModule($courseModule, $request->user());

        return back()->with('success', 'Module deleted.');
    }
}
