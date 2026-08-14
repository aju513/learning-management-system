<?php

namespace Database\Factories;

use App\Models\CourseChapter;
use Illuminate\Database\Eloquent\Factories\Factory;

class LearningMaterialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_chapter_id' => CourseChapter::factory(),
            'title' => fake()->sentence(3),
            'type' => 'article',
            'content' => '<p>'.fake()->paragraph().'</p>',
            'file_type' => null,
            'video_url' => null,
            'position' => 1,
            'is_required' => true,
        ];
    }
}
