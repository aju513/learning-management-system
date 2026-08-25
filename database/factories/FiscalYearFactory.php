<?php

namespace Database\Factories;

use App\Models\FiscalYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FiscalYear> */
class FiscalYearFactory extends Factory
{
    protected $model = FiscalYear::class;

    public function definition(): array
    {
        $start = now()->startOfYear();

        return [
            'name' => 'FY '.$start->year,
            'starts_on' => $start->toDateString(),
            'ends_on' => $start->copy()->endOfYear()->toDateString(),
            'status' => 'active',
            'attendance_threshold_days' => 90,
            'attendance_credit_points' => 10,
        ];
    }
}
