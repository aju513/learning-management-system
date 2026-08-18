<?php

namespace Database\Factories;

use App\Models\CourseAssessmentAttempt;
use App\Models\CourseAssessmentQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseAssessmentAnswerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_assessment_attempt_id' => CourseAssessmentAttempt::factory(),
            'course_assessment_question_id' => CourseAssessmentQuestion::factory(),
            'selected_option_ids' => [],
            'earned_marks' => 0,
            'is_correct' => false,
        ];
    }
}
