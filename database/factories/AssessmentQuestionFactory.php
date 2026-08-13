<?php

namespace Database\Factories;

use App\Models\Assessment;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssessmentQuestionFactory extends Factory
{
    public function definition(): array
    {
        return ['assessment_id' => Assessment::factory(), 'prompt' => fake()->sentence().'?', 'reference_answer' => null, 'type' => 'single_choice', 'marks' => 1, 'position' => 1];
    }
}
