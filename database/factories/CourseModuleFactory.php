<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseModuleFactory extends Factory
{
    public function definition(): array
    {
        return ['course_id' => Course::factory(), 'title' => fake()->sentence(3), 'description' => fake()->sentence(), 'position' => 1];
    }
}
