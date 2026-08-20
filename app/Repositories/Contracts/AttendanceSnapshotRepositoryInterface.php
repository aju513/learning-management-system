<?php

namespace App\Repositories\Contracts;

use App\Models\AttendanceSnapshot;
use App\Models\FiscalYear;
use App\Models\User;

interface AttendanceSnapshotRepositoryInterface
{
    public function forUser(FiscalYear $fiscalYear, User $user): ?AttendanceSnapshot;

    public function upsert(FiscalYear $fiscalYear, User $user, array $attributes): AttendanceSnapshot;
}
