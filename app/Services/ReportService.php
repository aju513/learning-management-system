<?php

namespace App\Services;

use App\Repositories\Contracts\ReportRepositoryInterface;

class ReportService
{
    public function __construct(private readonly ReportRepositoryInterface $reports) {}

    public function overview(): array
    {
        return $this->reports->reports();
    }

    public function courseReports(): array
    {
        return $this->reports->courseReports();
    }

    public function testReports(): array
    {
        return $this->reports->testReports();
    }
}
