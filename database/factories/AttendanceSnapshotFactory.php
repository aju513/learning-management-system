<?php

namespace Database\Factories;

use App\Models\AttendanceSnapshot;
use App\Models\FiscalYear;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AttendanceSnapshot> */
class AttendanceSnapshotFactory extends Factory
{
    protected $model = AttendanceSnapshot::class;

    public function definition(): array
    {
        return [
            'fiscal_year_id' => FiscalYear::factory(), 'user_id' => User::factory(), 'present_days' => 36,
            'source' => 'sandbox', 'status' => 'success', 'fetched_at' => now(),
        ];
    }
}
