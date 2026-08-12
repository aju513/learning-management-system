<?php

namespace App\Modules\SuperAdmin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $service) {}

    public function __invoke(): View
    {
        return view('modules.super-admin.dashboard', $this->service->forSuperAdmin() + ['title' => 'Super Admin Dashboard']);
    }
}
