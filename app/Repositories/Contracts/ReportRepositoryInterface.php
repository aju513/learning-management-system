<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface ReportRepositoryInterface
{
    public function superAdminDashboard(): array;

    public function adminDashboard(): array;

    public function instructorDashboard(User $instructor): array;

    public function traineeDashboard(User $trainee): array;

    public function reports(): array;

    public function courseReports(): array;

    public function testReports(): array;
}
