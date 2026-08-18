<?php

namespace Database\Factories;

use App\Models\CourseAssessment;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseAssessmentQuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_assessment_id' => CourseAssessment::factory(),
            'prompt' => fake()->sentence(),
            'type' => 'single_choice',
            'marks' => 1,
            'position' => 1,
        ];
    }
}
