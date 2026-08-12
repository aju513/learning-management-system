<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $service) {}

    public function __invoke(): View
    {
        return view('modules.admin.dashboard', $this->service->forAdmin() + ['title' => 'Admin Dashboard']);
    }
}
