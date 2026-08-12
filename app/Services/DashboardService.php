<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\ReportRepositoryInterface;

class DashboardService
{
    public function __construct(private readonly ReportRepositoryInterface $reports) {}

    public function forSuperAdmin(): array
    {
        return $this->reports->superAdminDashboard();
    }

    public function forAdmin(): array
    {
        return $this->reports->adminDashboard();
    }

    public function forInstructor(User $instructor): array
    {
        return $this->reports->instructorDashboard($instructor);
    }

    public function forTrainee(User $trainee): array
    {
        return $this->reports->traineeDashboard($trainee);
    }
}
