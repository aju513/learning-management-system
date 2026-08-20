<?php

namespace App\Repositories\Eloquent;

use App\Models\AttendanceSnapshot;
use App\Models\FiscalYear;
use App\Models\User;
use App\Repositories\Contracts\AttendanceSnapshotRepositoryInterface;

class AttendanceSnapshotRepository implements AttendanceSnapshotRepositoryInterface
{
    public function forUser(FiscalYear $fiscalYear, User $user): ?AttendanceSnapshot
    {
        return AttendanceSnapshot::query()->where('fiscal_year_id', $fiscalYear->id)->where('user_id', $user->id)->first();
    }

    public function upsert(FiscalYear $fiscalYear, User $user, array $attributes): AttendanceSnapshot
    {
        return AttendanceSnapshot::query()->updateOrCreate(
            ['fiscal_year_id' => $fiscalYear->id, 'user_id' => $user->id],
            $attributes,
        );
    }
}
