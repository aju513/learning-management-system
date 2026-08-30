<?php

namespace App\Modules\Trainee\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Trainee\Http\Requests\OverviewRequest;
use App\Services\TraineeOverviewService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly TraineeOverviewService $service) {}

    public function __invoke(OverviewRequest $request): View
    {
        return view('modules.trainee.dashboard', $this->service->for($request->user()) + ['title' => 'Overview']);
    }
}
