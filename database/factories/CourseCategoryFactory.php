<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CourseCategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return ['name' => Str::title($name), 'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999), 'description' => fake()->sentence(), 'is_active' => true];
    }
}
