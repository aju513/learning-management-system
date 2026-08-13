<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseChapter\DeleteCourseChapterRequest;
use App\Http\Requests\CourseChapter\MoveCourseChapterRequest;
use App\Http\Requests\CourseChapter\StoreCourseChapterRequest;
use App\Http\Requests\CourseChapter\UpdateCourseChapterRequest;
use App\Models\CourseChapter;
use App\Models\CourseModule;
use App\Services\CourseService;
use Illuminate\Http\RedirectResponse;

class CourseChapterController extends Controller
{
    public function __construct(private readonly CourseService $service) {}

    public function store(StoreCourseChapterRequest $request, CourseModule $courseModule): RedirectResponse
    {
        $this->service->createChapter($courseModule, $request->validated(), $request->user());

        return back()->with('success', 'Chapter added.');
    }

    public function update(UpdateCourseChapterRequest $request, CourseChapter $courseChapter): RedirectResponse
    {
        $this->service->updateChapter($courseChapter, $request->validated(), $request->user());

        return back()->with('success', 'Chapter updated.');
    }

    public function move(MoveCourseChapterRequest $request, CourseChapter $courseChapter): RedirectResponse
    {
        $this->service->moveChapter($courseChapter, $request->validated('direction'), $request->user());

        return back()->with('success', 'Chapter reordered.');
    }

    public function destroy(DeleteCourseChapterRequest $request, CourseChapter $courseChapter): RedirectResponse
    {
        $this->service->deleteChapter($courseChapter, $request->user());

        return back()->with('success', 'Chapter deleted.');
    }
}
