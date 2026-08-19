<?php

use App\Models\Course;
use App\Models\CourseChapter;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\LearningMaterial;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->artisan('admin:permissions-sync')->assertSuccessful();
});

function chapterAuthor(string $role = 'instructor'): User
{
    $user = User::factory()->create();
    $user->syncRoles([Role::findByName($role)]);

    return $user;
}

test('creating a module also creates its first chapter', function () {
    $instructor = chapterAuthor();
    $course = Course::factory()->for($instructor, 'instructor')->create();

    $response = $this->actingAs($instructor)->post(route('instructor.course-modules.store', $course), [
        'title' => 'Foundations',
        'description' => 'Start here.',
    ])->assertRedirect();

    $module = $course->modules()->firstOrFail();
    $chapter = $module->chapters()->firstOrFail();

    expect($chapter->title)->toBe('Chapter 1')
        ->and($chapter->position)->toBe(1)
        ->and($response->headers->get('Location'))->toContain('#module-'.$module->id)
        ->and(Activity::where('event', 'course-chapter.created')->where('subject_id', $chapter->id)->exists())->toBeTrue();
});

test('an owner can manage ordered chapters but cannot delete one containing materials', function () {
    $instructor = chapterAuthor();
    $course = Course::factory()->for($instructor, 'instructor')->create();
    $module = CourseModule::factory()->for($course)->create();
    $first = CourseChapter::factory()->for($module, 'module')->create(['title' => 'First', 'position' => 1]);

    $response = $this->actingAs($instructor)->post(route('instructor.course-chapters.store', $module), [
        'title' => 'Second',
        'description' => 'Second chapter.',
    ])->assertRedirect();
    $second = $module->chapters()->where('title', 'Second')->firstOrFail();
    expect($response->headers->get('Location'))->toContain('#chapter-'.$second->id);

    $this->actingAs($instructor)->put(route('instructor.course-chapters.update', $second), [
        'title' => 'Updated second',
        'description' => 'Updated.',
    ])->assertRedirect();
    $this->actingAs($instructor)->patch(route('instructor.course-chapters.move', $second), ['direction' => 'up'])->assertRedirect();

    expect($second->fresh()->title)->toBe('Updated second')
        ->and($second->fresh()->position)->toBe(1)
        ->and($first->fresh()->position)->toBe(2);

    $firstMaterial = LearningMaterial::factory()->for($second, 'chapter')->create(['position' => 1]);
    $secondMaterial = LearningMaterial::factory()->for($second, 'chapter')->create(['position' => 2]);
    $this->actingAs($instructor)->patch(route('instructor.learning-materials.move', $secondMaterial), ['direction' => 'up'])->assertRedirect();
    expect($secondMaterial->fresh()->position)->toBe(1)
        ->and($firstMaterial->fresh()->position)->toBe(2);

    $this->actingAs($instructor)->delete(route('instructor.course-chapters.destroy', $second))
        ->assertSessionHasErrors('chapter');
    expect($second->fresh())->not->toBeNull();
});

test('owners can bulk reorder modules and chapters with contiguous positions', function () {
    $instructor = chapterAuthor();
    $course = Course::factory()->for($instructor, 'instructor')->create();
    $firstModule = CourseModule::factory()->for($course)->create(['position' => 1]);
    $secondModule = CourseModule::factory()->for($course)->create(['position' => 2]);
    $thirdModule = CourseModule::factory()->for($course)->create(['position' => 3]);
    $firstChapter = CourseChapter::factory()->for($secondModule, 'module')->create(['position' => 1]);
    $secondChapter = CourseChapter::factory()->for($secondModule, 'module')->create(['position' => 2]);
    $firstMaterial = LearningMaterial::factory()->for($firstChapter, 'chapter')->create(['position' => 1]);
    $secondMaterial = LearningMaterial::factory()->for($firstChapter, 'chapter')->create(['position' => 2]);

    $this->actingAs($instructor)
        ->patchJson(route('instructor.course-modules.reorder', $course), [
            'module_ids' => [$thirdModule->id, $firstModule->id, $secondModule->id],
        ])
        ->assertOk();

    $this->actingAs($instructor)
        ->patchJson(route('instructor.course-chapters.reorder', $secondModule), [
            'chapter_ids' => [$secondChapter->id, $firstChapter->id],
        ])
        ->assertOk();

    $this->actingAs($instructor)
        ->patchJson(route('instructor.learning-materials.reorder', $firstChapter), [
            'material_ids' => [$secondMaterial->id, $firstMaterial->id],
        ])
        ->assertOk();

    expect($thirdModule->fresh()->position)->toBe(1)
        ->and($firstModule->fresh()->position)->toBe(2)
        ->and($secondModule->fresh()->position)->toBe(3)
        ->and($secondChapter->fresh()->position)->toBe(1)
        ->and($firstChapter->fresh()->position)->toBe(2)
        ->and($secondMaterial->fresh()->position)->toBe(1)
        ->and($firstMaterial->fresh()->position)->toBe(2)
        ->and(Activity::where('event', 'course-modules.reordered')->exists())->toBeTrue()
        ->and(Activity::where('event', 'course-chapters.reordered')->exists())->toBeTrue()
        ->and(Activity::where('event', 'learning-materials.reordered')->exists())->toBeTrue();
});

test('bulk curriculum reorder rejects incomplete or foreign collections', function () {
    $instructor = chapterAuthor();
    $other = chapterAuthor();
    $course = Course::factory()->for($instructor, 'instructor')->create();
    $first = CourseModule::factory()->for($course)->create(['position' => 1]);
    $second = CourseModule::factory()->for($course)->create(['position' => 2]);
    $foreignCourse = Course::factory()->for($other, 'instructor')->create();
    $foreignModule = CourseModule::factory()->for($foreignCourse)->create();
    $chapter = CourseChapter::factory()->for($first, 'module')->create();
    $material = LearningMaterial::factory()->for($chapter, 'chapter')->create();
    $foreignChapter = CourseChapter::factory()->for($foreignModule, 'module')->create();
    $foreignMaterial = LearningMaterial::factory()->for($foreignChapter, 'chapter')->create();

    $this->actingAs($instructor)
        ->patchJson(route('instructor.course-modules.reorder', $course), ['module_ids' => [$first->id]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('module_ids');

    $this->actingAs($instructor)
        ->patchJson(route('instructor.course-modules.reorder', $course), ['module_ids' => [$first->id, $foreignModule->id]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('module_ids');

    $this->actingAs($other)
        ->patchJson(route('instructor.course-modules.reorder', $course), ['module_ids' => [$first->id, $second->id]])
        ->assertForbidden();

    $this->actingAs($instructor)
        ->patchJson(route('instructor.learning-materials.reorder', $chapter), ['material_ids' => [$foreignMaterial->id]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('material_ids');

    $this->actingAs($other)
        ->patchJson(route('instructor.learning-materials.reorder', $chapter), ['material_ids' => [$material->id]])
        ->assertForbidden();

    expect($first->fresh()->position)->toBe(1)->and($second->fresh()->position)->toBe(2);
});

test('course curriculum renders collapsible reorder handles without legacy arrow controls', function () {
    $instructor = chapterAuthor();
    $course = Course::factory()->for($instructor, 'instructor')->create();
    $module = CourseModule::factory()->for($course)->create(['title' => 'Foundations']);
    $chapter = CourseChapter::factory()->for($module, 'module')->create(['title' => 'Introduction']);
    LearningMaterial::factory()->for($chapter, 'chapter')->create(['title' => 'Welcome']);

    $this->actingAs($instructor)
        ->get(route('instructor.courses.show', $course))
        ->assertOk()
        ->assertSee('aria-controls="module-panel-'.$module->id.'"', false)
        ->assertSee('aria-controls="chapter-panel-'.$chapter->id.'"', false)
        ->assertSee('data-reorder-url=', false)
        ->assertSee('class="handle', false)
        ->assertSee('bi-plus', false)
        ->assertSee('bi-dash', false)
        ->assertSee('x-collapse.duration.300ms', false)
        ->assertSee('data-material-list', false)
        ->assertSee('Page 1', false)
        ->assertSee('bi-file-earmark-text', false)
        ->assertSee('focus-curriculum-chapter', false)
        ->assertSee("window.location.hash === '#chapter-", false)
        ->assertSee('open-module-create-modal', false)
        ->assertSee('open-chapter-create-modal', false)
        ->assertSee('Add Module', false)
        ->assertSee('Add Chapter', false)
        ->assertSee('bg-gray-900/30', false)
        ->assertDontSee('backdrop-blur-[32px]', false)
        ->assertDontSee('new-module-title', false)
        ->assertDontSee('new-chapter-title', false)
        ->assertSee('x-data="{ expanded: false }"', false)
        ->assertSee('open-module-edit-modal', false)
        ->assertSee('open-chapter-edit-modal', false)
        ->assertSee('Edit Module', false)
        ->assertSee('Edit Chapter', false)
        ->assertDontSee('x-show="editing"', false)
        ->assertDontSee('x-show="editingChapter"', false)
        ->assertDontSee('title="Move up"', false)
        ->assertDontSee('title="Move chapter up"', false);
});

test('course ownership authorization tolerates database id scalar differences', function () {
    $instructor = chapterAuthor();
    $course = Course::factory()->for($instructor, 'instructor')->create();
    $instructor->setAttribute('id', (string) $instructor->id);

    $this->actingAs($instructor)
        ->get(route('instructor.courses.show', $course))
        ->assertOk();
});

test('chapter management and material authoring reject a foreign course owner', function () {
    $owner = chapterAuthor();
    $other = chapterAuthor();
    $course = Course::factory()->for($owner, 'instructor')->create();
    $module = CourseModule::factory()->for($course)->create();
    $chapter = CourseChapter::factory()->for($module, 'module')->create();
    $material = LearningMaterial::factory()->for($chapter, 'chapter')->create();

    $this->actingAs($other)->post(route('instructor.course-chapters.store', $module), ['title' => 'Denied'])->assertForbidden();
    $this->actingAs($other)->get(route('instructor.learning-materials.create', $chapter))->assertForbidden();
    $this->actingAs($other)->get(route('instructor.learning-materials.edit', $material))->assertForbidden();
});

test('material creation and editing use dedicated preview pages and clean incompatible file data', function () {
    Storage::fake('local');
    $instructor = chapterAuthor();
    $course = Course::factory()->for($instructor, 'instructor')->create();
    $module = CourseModule::factory()->for($course)->create();
    $chapter = CourseChapter::factory()->for($module, 'module')->create(['title' => 'Documents']);

    $this->actingAs($instructor)->get(route('instructor.learning-materials.create', $chapter))
        ->assertOk()
        ->assertSee('Preview material')
        ->assertSee('animate-pulse')
        ->assertSee('open-material-preview')
        ->assertSee('Trainee preview')
        ->assertSee('Documents')
        ->assertDontSee('External link')
        ->assertDontSee('External URL');

    $this->actingAs($instructor)->post(route('instructor.learning-materials.store', $chapter), [
        'title' => 'Missing guide',
        'type' => 'file',
        'file_type' => 'pdf',
        'duration_minutes' => 5,
        'is_required' => 1,
    ])->assertSessionHasErrors('file');

    $this->actingAs($instructor)->post(route('instructor.learning-materials.store', $chapter), [
        'title' => 'Policy guide',
        'type' => 'file',
        'file_type' => 'pdf',
        'file' => UploadedFile::fake()->create('policy.pdf', 100, 'application/pdf'),
        'duration_minutes' => 15,
        'is_required' => 1,
    ])->assertRedirect(route('instructor.courses.show', $course).'#chapter-'.$chapter->id);

    $material = $chapter->materials()->firstOrFail();
    $oldPath = $material->file_path;
    Storage::disk('local')->assertExists($oldPath);

    $this->actingAs($instructor)->get(route('instructor.learning-materials.edit', $material))
        ->assertOk()
        ->assertSee('Trainee preview')
        ->assertSee('Policy guide');

    $this->actingAs($instructor)->put(route('instructor.learning-materials.update', $material), [
        'title' => 'Policy article',
        'type' => 'article',
        'content' => '<p onclick="alert(1)">Safe policy</p><script>alert(2)</script>',
        'duration_minutes' => 10,
        'is_required' => 1,
    ])->assertRedirect(route('instructor.courses.show', $course).'#chapter-'.$chapter->id);

    $material->refresh();
    expect($material->type->value)->toBe('article')
        ->and($material->content)->toBe('<p>Safe policy</p>alert(2)')
        ->and($material->file_path)->toBeNull()
        ->and($material->original_filename)->toBeNull();
    Storage::disk('local')->assertMissing($oldPath);
});

test('publishing requires every module and chapter to contain learning material', function () {
    $instructor = chapterAuthor();
    $course = Course::factory()->for($instructor, 'instructor')->create();

    $this->actingAs($instructor)->post(route('instructor.course-modules.store', $course), ['title' => 'Empty module'])->assertRedirect();
    $chapter = $course->modules()->firstOrFail()->chapters()->firstOrFail();

    $this->actingAs($instructor)->patch(route('instructor.courses.status', $course), ['status' => 'published'])
        ->assertSessionHasErrors('status');

    LearningMaterial::factory()->for($chapter, 'chapter')->create();
    $this->actingAs($instructor)->patch(route('instructor.courses.status', $course), ['status' => 'published'])->assertRedirect();
    expect($course->fresh()->status->value)->toBe('published');
});

test('owners can create the canonical material types and course assessment materials', function () {
    Storage::fake('local');
    $instructor = chapterAuthor();
    $course = Course::factory()->for($instructor, 'instructor')->create();
    $module = CourseModule::factory()->for($course)->create();
    $chapter = CourseChapter::factory()->for($module, 'module')->create();

    $this->actingAs($instructor)->post(route('instructor.learning-materials.store', $chapter), [
        'title' => 'Routing article', 'type' => 'article', 'content' => '<p>Read this article.</p>', 'is_required' => 1,
    ])->assertRedirect();

    $this->actingAs($instructor)->post(route('instructor.learning-materials.store', $chapter), [
        'title' => 'Video lesson', 'type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=abc123',
        'content' => '<p>Watch the lesson.</p><script>alert(1)</script>', 'is_required' => 1,
    ])->assertRedirect();

    $this->actingAs($instructor)->post(route('instructor.learning-materials.store', $chapter), [
        'title' => 'Reference PDF', 'type' => 'file', 'file_type' => 'pdf',
        'file' => UploadedFile::fake()->create('reference.pdf', 100, 'application/pdf'), 'is_required' => 1,
    ])->assertRedirect();

    $this->actingAs($instructor)->post(route('instructor.learning-materials.store', $chapter), [
        'title' => 'Official documentation', 'type' => 'link', 'external_url' => 'https://laravel.com/docs',
        'content' => '<p>Read the documentation.</p>', 'is_required' => 0,
    ])->assertRedirect();

    $assessmentResponse = $this->actingAs($instructor)->post(route('instructor.learning-materials.store', $chapter), [
        'title' => 'Knowledge check', 'type' => 'course_assessment', 'passing_percentage' => 70,
        'content' => '<p>Complete the assessment.</p>', 'is_required' => 1,
    ]);

    $materials = $chapter->materials()->orderBy('position')->get();
    $assessmentResponse->assertRedirect(route('instructor.course-assessments.show', $materials[4]->courseAssessment));
    expect($materials->pluck('type')->map(fn ($type) => $type->value)->all())->toBe(['article', 'video', 'file', 'link', 'course_assessment'])
        ->and($materials[0]->content)->toBe('<p>Read this article.</p>')
        ->and($materials[1]->video_url)->toBe('https://www.youtube.com/watch?v=abc123')
        ->and($materials[1]->content)->toBe('<p>Watch the lesson.</p>alert(1)')
        ->and($materials[2]->file_type)->toBe('pdf')
        ->and($materials[2]->file_path)->not->toBeNull()
        ->and($materials[3]->external_url)->toBe('https://laravel.com/docs')
        ->and($materials[4]->courseAssessment->passing_percentage)->toBe('70.00');
});

test('trainee navigation shows chapters while the catalog keeps a flat module preview', function () {
    $instructor = chapterAuthor();
    $trainee = chapterAuthor('trainee');
    $course = Course::factory()->published()->for($instructor, 'instructor')->create(['navigation_mode' => 'sequential']);
    $module = CourseModule::factory()->for($course)->create(['title' => 'Module A']);
    $firstChapter = CourseChapter::factory()->for($module, 'module')->create(['title' => 'Private Chapter Alpha', 'position' => 1]);
    $secondChapter = CourseChapter::factory()->for($module, 'module')->create(['title' => 'Private Chapter Beta', 'position' => 2]);
    $first = LearningMaterial::factory()->for($firstChapter, 'chapter')->create(['title' => 'First item']);
    $second = LearningMaterial::factory()->for($secondChapter, 'chapter')->create(['title' => 'Second item']);
    $enrollment = Enrollment::factory()->for($course)->for($trainee, 'trainee')->create();

    $this->actingAs($trainee)->get(route('learning.catalog.show', $course))
        ->assertOk()
        ->assertSee('First item')
        ->assertSee('Second item')
        ->assertDontSee('Private Chapter Alpha')
        ->assertDontSee('Private Chapter Beta');

    $this->actingAs($trainee)->get(route('learning.courses.materials.show', [$enrollment, $second]))->assertForbidden();
    $this->actingAs($trainee)->get(route('learning.courses.materials.show', [$enrollment, $first]))
        ->assertOk()
        ->assertSee('Private Chapter Alpha')
        ->assertSee('Private Chapter Beta');
    $this->actingAs($trainee)->post(route('learning.courses.materials.complete', [$enrollment, $first]))->assertRedirect();
    expect((float) $enrollment->fresh()->progress_percentage)->toBe(50.0);
    $this->actingAs($trainee)->get(route('learning.courses.materials.show', [$enrollment, $second]))->assertOk();
});
