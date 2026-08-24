<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\IndexReportRequest;
use App\Services\ReportService;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $service) {}

    public function __invoke(IndexReportRequest $request): View
    {
        return view('pages.admin.reports.index', $this->service->overview() + ['title' => 'LMS Reports']);
    }

    public function courses(IndexReportRequest $request): View
    {
        return view('pages.admin.reports.courses', $this->service->courseReports() + ['title' => 'Course Reports']);
    }

    public function tests(IndexReportRequest $request): View
    {
        return view('pages.admin.reports.tests', $this->service->testReports() + ['title' => 'Test Reports']);
    }
}
