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

    $this->actingAs($instructor)->post(route('instructor.course-modules.store', $course), [
        'title' => 'Foundations',
        'description' => 'Start here.',
    ])->assertRedirect();

    $module = $course->modules()->firstOrFail();
    $chapter = $module->chapters()->firstOrFail();

    expect($chapter->title)->toBe('Chapter 1')
        ->and($chapter->position)->toBe(1)
        ->and(Activity::where('event', 'course-chapter.created')->where('subject_id', $chapter->id)->exists())->toBeTrue();
});

test('an owner can manage ordered chapters but cannot delete one containing materials', function () {
    $instructor = chapterAuthor();
    $course = Course::factory()->for($instructor, 'instructor')->create();
    $module = CourseModule::factory()->for($course)->create();
    $first = CourseChapter::factory()->for($module, 'module')->create(['title' => 'First', 'position' => 1]);

    $this->actingAs($instructor)->post(route('instructor.course-chapters.store', $module), [
        'title' => 'Second',
        'description' => 'Second chapter.',
    ])->assertRedirect();
    $second = $module->chapters()->where('title', 'Second')->firstOrFail();

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
        ->assertSee('Trainee preview')
        ->assertSee('Documents');

    $this->actingAs($instructor)->post(route('instructor.learning-materials.store', $chapter), [
        'title' => 'Missing guide',
        'type' => 'pdf',
        'duration_minutes' => 5,
        'is_required' => 1,
    ])->assertSessionHasErrors('file');

    $this->actingAs($instructor)->post(route('instructor.learning-materials.store', $chapter), [
        'title' => 'Policy guide',
        'type' => 'pdf',
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
