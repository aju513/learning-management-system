<?php

namespace Database\Factories;

use App\Models\CourseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CourseFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'category_id' => CourseCategory::factory(),
            'instructor_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 99999),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraphs(2, true),
            'difficulty' => 'beginner',
            'estimated_duration_minutes' => 60,
            'status' => 'draft',
            'navigation_mode' => 'free',
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => 'published', 'published_at' => now()]);
    }
}
