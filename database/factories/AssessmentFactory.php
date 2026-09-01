<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssessmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'category_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->sentence(),
            'instructions' => fake()->sentence(),
            'duration_minutes' => 30,
            'passing_percentage' => 60,
            'max_attempts' => 1,
            'status' => 'draft',
            'show_results' => true,
            'availability_scope' => 'all',
            'required_training_key' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => 'published']);
    }
}
