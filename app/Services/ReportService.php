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
}
