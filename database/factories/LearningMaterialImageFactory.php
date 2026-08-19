<?php

namespace Database\Factories;

use App\Models\CourseChapter;
use App\Models\LearningMaterialImage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LearningMaterialImageFactory extends Factory
{
    protected $model = LearningMaterialImage::class;

    public function definition(): array
    {
        $uuid = (string) Str::uuid();

        return [
            'uuid' => $uuid,
            'course_chapter_id' => CourseChapter::factory(),
            'learning_material_id' => null,
            'uploaded_by' => User::factory(),
            'disk' => 'local',
            'path' => "lms/content-images/{$uuid}.png",
            'mime_type' => 'image/png',
            'original_filename' => 'image.png',
            'size' => 100,
            'expires_at' => now()->addDay(),
        ];
    }
}
