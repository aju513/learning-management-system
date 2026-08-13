<?php

namespace Database\Factories;

use App\Models\CourseModule;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseChapterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_module_id' => CourseModule::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'position' => 1,
        ];
    }
}
