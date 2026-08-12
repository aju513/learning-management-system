<?php

namespace App\Modules\Trainee\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Modules\Trainee\Http\Requests\IndexCatalogRequest;
use App\Modules\Trainee\Http\Requests\ShowCatalogCourseRequest;
use App\Repositories\Contracts\CourseRepositoryInterface;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __construct(private readonly CourseRepositoryInterface $courses) {}

    public function index(IndexCatalogRequest $request): View
    {
        return view('modules.trainee.catalog.index', [
            'courses' => $this->courses->paginatePublishedCatalog($request->validated(), $request->user()),
            'categories' => $this->courses->activeCategories(),
            'title' => 'Course Catalog',
        ]);
    }

    public function show(ShowCatalogCourseRequest $request, Course $course): View
    {
        return view('modules.trainee.catalog.show', [
            'course' => $this->courses->findPublishedCatalogCourse($course, $request->user()),
            'title' => $course->title,
        ]);
    }
}
