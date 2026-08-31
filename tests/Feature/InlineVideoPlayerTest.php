<?php

use App\Models\Course;
use App\Models\CourseChapter;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\LearningMaterial;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->artisan('admin:permissions-sync')->assertSuccessful();
});

function inlineVideoTrainee(): User
{
    $user = User::factory()->create();
    $user->syncRoles([Role::findByName('trainee')]);

    return $user;
}

function inlineVideoEnrollment(User $trainee, array $materialAttributes): array
{
    $instructor = User::factory()->create();
    $instructor->syncRoles([Role::findByName('instructor')]);
    $course = Course::factory()->published()->for($instructor, 'instructor')->create([
        'navigation_mode' => 'free',
    ]);
    $module = CourseModule::factory()->for($course)->create();
    $chapter = CourseChapter::factory()->for($module, 'module')->create();
    $material = LearningMaterial::factory()->for($chapter, 'chapter')->create($materialAttributes);
    $enrollment = Enrollment::factory()->for($course)->for($trainee, 'trainee')->create();

    return [$enrollment, $material];
}

test('YouTube video materials render an embedded player in the course player', function (): void {
    $trainee = inlineVideoTrainee();
    [$enrollment, $material] = inlineVideoEnrollment($trainee, [
        'type' => 'video',
        'content' => null,
        'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    ]);

    $this->actingAs($trainee)->get(route('learning.courses.materials.show', [$enrollment, $material]))
        ->assertOk()
        ->assertSee('<iframe', false)
        ->assertSee('https://www.youtube.com/embed/dQw4w9WgXcQ', false)
        ->assertDontSee('Open video')
        ->assertDontSee('target="_blank"', false);
});

test('uploaded video materials render an authorized native player', function (): void {
    Storage::fake('local');
    $trainee = inlineVideoTrainee();
    [$enrollment, $material] = inlineVideoEnrollment($trainee, [
        'type' => 'video',
        'content' => null,
        'video_url' => null,
        'file_path' => 'lms/materials/course-video.mp4',
        'original_filename' => 'course-video.mp4',
        'mime_type' => 'video/mp4',
    ]);
    Storage::disk('local')->put($material->file_path, 'video bytes');

    $this->actingAs($trainee)->get(route('learning.courses.materials.show', [$enrollment, $material]))
        ->assertOk()
        ->assertSee('<video', false)
        ->assertSee(route('learning.courses.materials.stream', [$enrollment, $material]), false)
        ->assertDontSee('Download video');

    $this->actingAs($trainee)->get(route('learning.courses.materials.stream', [$enrollment, $material]))
        ->assertOk()
        ->assertHeader('Content-Type', 'video/mp4')
        ->assertHeader('Content-Disposition', 'inline; filename="course-video.mp4"');
});

test('uploaded video streams are limited to the enrolled trainee', function (): void {
    Storage::fake('local');
    $trainee = inlineVideoTrainee();
    $outsider = inlineVideoTrainee();
    [$enrollment, $material] = inlineVideoEnrollment($trainee, [
        'type' => 'video',
        'content' => null,
        'video_url' => null,
        'file_path' => 'lms/materials/private-video.mp4',
        'mime_type' => 'video/mp4',
    ]);
    Storage::disk('local')->put($material->file_path, 'video bytes');

    $this->actingAs($outsider)->get(route('learning.courses.materials.stream', [$enrollment, $material]))
        ->assertForbidden();
});
