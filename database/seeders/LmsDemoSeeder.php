<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseChapter;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\LearningMaterial;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LmsDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->user('Demo Super Administrator', 'superadmin@example.com', 'super-admin');
            $admin = $this->user('LMS Administrator', 'lms.admin@example.com', 'admin');
            $instructor = $this->user('Demo Instructor', 'instructor@example.com', 'instructor');
            $trainee = $this->user('Demo Trainee', 'trainee@example.com', 'trainee');

            $category = CourseCategory::query()->firstOrCreate(
                ['slug' => 'government-training'],
                ['name' => 'Government Training', 'description' => 'Core professional development courses.', 'is_active' => true],
            );
            $course = Course::query()->firstOrCreate(
                ['slug' => 'local-governance-essentials'],
                [
                    'category_id' => $category->id, 'instructor_id' => $instructor->id,
                    'title' => 'Local Governance Essentials', 'short_description' => 'A practical introduction to local governance responsibilities and planning.',
                    'description' => 'Learn the foundations of local governance, planning, accountability, and public service delivery.',
                    'difficulty' => 'beginner', 'estimated_duration_minutes' => 75, 'status' => 'published',
                    'navigation_mode' => 'sequential', 'published_at' => now(),
                ],
            );
            $module = CourseModule::query()->firstOrCreate(
                ['course_id' => $course->id, 'position' => 1],
                ['title' => 'Foundations', 'description' => 'Core concepts and responsibilities.'],
            );
            $chapter = CourseChapter::query()->firstOrCreate(
                ['course_module_id' => $module->id, 'position' => 1],
                ['title' => 'Chapter 1', 'description' => 'Core concepts and responsibilities.'],
            );
            LearningMaterial::query()->firstOrCreate(
                ['course_chapter_id' => $chapter->id, 'position' => 1],
                ['title' => 'Understanding Local Governance', 'type' => 'article', 'content' => '<p>Local governance brings public decisions and services closer to citizens.</p><p>Complete this article before continuing to the module quiz.</p>', 'duration_minutes' => 10, 'is_required' => true],
            );
            Enrollment::query()->firstOrCreate(
                ['course_id' => $course->id, 'user_id' => $trainee->id],
                ['enrolled_by' => $admin->id, 'status' => 'active', 'progress_percentage' => 0, 'enrolled_at' => now()],
            );
        });
    }

    private function user(string $name, string $email, string $role): User
    {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make('Password123!'), 'status' => 'active'],
        );
        $user->syncRoles([$role]);

        return $user;
    }
}
