<?php

namespace Database\Factories;

use App\Models\CourseAssessmentQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseAssessmentOptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_assessment_question_id' => CourseAssessmentQuestion::factory(),
            'option_text' => fake()->sentence(2),
            'is_correct' => false,
            'position' => 1,
        ];
    }
}
