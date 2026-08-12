<?php

namespace App\Modules\Instructor\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $service) {}

    public function __invoke(): View
    {
        return view('modules.instructor.dashboard', $this->service->forInstructor(request()->user()) + ['title' => 'Instructor Dashboard']);
    }
}
