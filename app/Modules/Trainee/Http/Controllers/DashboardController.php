<?php

namespace App\Modules\Trainee\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $service) {}

    public function __invoke(): View
    {
        return view('modules.trainee.dashboard', $this->service->forTrainee(request()->user()) + ['title' => 'Learning Dashboard']);
    }
}
