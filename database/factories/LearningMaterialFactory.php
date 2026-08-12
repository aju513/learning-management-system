<?php

namespace Database\Factories;

use App\Models\CourseModule;
use Illuminate\Database\Eloquent\Factories\Factory;

class LearningMaterialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_module_id' => CourseModule::factory(),
            'title' => fake()->sentence(3),
            'type' => 'article',
            'content' => '<p>'.fake()->paragraph().'</p>',
            'position' => 1,
            'is_required' => true,
        ];
    }
}
