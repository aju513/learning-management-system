<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseCategory\DeleteCourseCategoryRequest;
use App\Http\Requests\CourseCategory\IndexCourseCategoryRequest;
use App\Http\Requests\CourseCategory\StoreCourseCategoryRequest;
use App\Http\Requests\CourseCategory\UpdateCourseCategoryRequest;
use App\Models\CourseCategory;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Services\CourseService;
use App\Support\PortalRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CourseCategoryController extends Controller
{
    public function __construct(private readonly CourseRepositoryInterface $courses, private readonly CourseService $service) {}

    public function index(IndexCourseCategoryRequest $request): View
    {
        return view('pages.admin.course-categories.index', ['categories' => $this->courses->paginateCategories($request->validated()), 'title' => 'Course Categories']);
    }

    public function create(): View
    {
        return view('pages.admin.course-categories.create', ['category' => new CourseCategory(['is_active' => true]), 'title' => 'Create Category']);
    }

    public function store(StoreCourseCategoryRequest $request): RedirectResponse
    {
        $this->service->createCategory($request->validated(), $request->user());

        return redirect()->route(PortalRoute::name('course-categories.index'))->with('success', 'Course category created.');
    }

    public function edit(CourseCategory $courseCategory): View
    {
        return view('pages.admin.course-categories.edit', ['category' => $courseCategory, 'title' => 'Edit Category']);
    }

    public function update(UpdateCourseCategoryRequest $request, CourseCategory $courseCategory): RedirectResponse
    {
        $this->service->updateCategory($courseCategory, $request->validated(), $request->user());

        return redirect()->route(PortalRoute::name('course-categories.index'))->with('success', 'Course category updated.');
    }

    public function destroy(DeleteCourseCategoryRequest $request, CourseCategory $courseCategory): RedirectResponse
    {
        $this->service->deleteCategory($courseCategory, $request->user());

        return back()->with('success', 'Course category deleted.');
    }
}
