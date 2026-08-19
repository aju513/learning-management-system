<?php

namespace Database\Factories;

use App\Models\LearningMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseAssessmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'learning_material_id' => LearningMaterial::factory()->state(['type' => 'course_assessment']),
            'passing_percentage' => 60,
        ];
    }
}
