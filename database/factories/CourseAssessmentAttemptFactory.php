<?php

namespace Database\Factories;

use App\Models\CourseAssessment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseAssessmentAttemptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_assessment_id' => CourseAssessment::factory(),
            'user_id' => User::factory(),
            'attempt_number' => 1,
            'status' => 'in_progress',
            'started_at' => now(),
        ];
    }
}
