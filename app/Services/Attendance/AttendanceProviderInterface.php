<?php

namespace App\Services\Attendance;

use App\Models\FiscalYear;
use App\Models\User;

interface AttendanceProviderInterface
{
    public function presentDays(User $user, FiscalYear $fiscalYear): int;

    public function name(): string;
}
