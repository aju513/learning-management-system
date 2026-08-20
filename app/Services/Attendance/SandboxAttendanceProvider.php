<?php

namespace App\Services\Attendance;

use App\Models\FiscalYear;
use App\Models\User;

class SandboxAttendanceProvider implements AttendanceProviderInterface
{
    public function presentDays(User $user, FiscalYear $fiscalYear): int
    {
        return max(0, (int) config('services.tmis.attendance.sandbox_days', 0));
    }

    public function name(): string
    {
        return 'sandbox';
    }
}
