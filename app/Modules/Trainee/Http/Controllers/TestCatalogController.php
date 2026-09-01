<?php

namespace App\Modules\Trainee\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Modules\Trainee\Http\Requests\IndexTestCatalogRequest;
use App\Modules\Trainee\Http\Requests\ShowTestCatalogRequest;
use App\Services\TraineeTestCatalogService;
use Illuminate\View\View;

class TestCatalogController extends Controller
{
    public function __construct(private readonly TraineeTestCatalogService $service) {}

    public function index(IndexTestCatalogRequest $request): View
    {
        return view('modules.trainee.tests.index', $this->service->index($request->user(), $request->validated()) + ['title' => 'Test Catalog']);
    }

    public function show(ShowTestCatalogRequest $request, Assessment $assessment): View
    {
        return view('modules.trainee.tests.show', $this->service->show($request->user(), $assessment->id) + ['title' => $assessment->title]);
    }
}
