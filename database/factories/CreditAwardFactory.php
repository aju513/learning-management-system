<?php

namespace Database\Factories;

use App\Models\CreditAward;
use App\Models\FiscalYear;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CreditAward> */
class CreditAwardFactory extends Factory
{
    protected $model = CreditAward::class;

    public function definition(): array
    {
        return [
            'fiscal_year_id' => FiscalYear::factory(), 'user_id' => User::factory(), 'source_type' => 'course_completion',
            'source_key' => 'course:'.$this->faker->unique()->numberBetween(1, 999999), 'source_label' => $this->faker->sentence(3),
            'points' => 5, 'status' => 'eligible', 'eligible_at' => now(),
        ];
    }
}
