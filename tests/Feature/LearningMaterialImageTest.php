<?php

use App\Models\Course;
use App\Models\CourseChapter;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->artisan('admin:permissions-sync')->assertSuccessful();
});

function imageAuthor(string $role = 'instructor'): User
{
    $user = User::factory()->create();
    $user->syncRoles([Role::findByName($role)]);

    return $user;
}

function imageChapter(User $owner): CourseChapter
{
    $course = Course::factory()->for($owner, 'instructor')->create();
    $module = CourseModule::factory()->for($course)->create();

    return CourseChapter::factory()->for($module, 'module')->create();
}

test('an authorized owner can upload and save an image in material content', function (): void {
    Storage::fake('local');
    $owner = imageAuthor();
    $chapter = imageChapter($owner);

    $upload = $this->actingAs($owner)->post(route('instructor.learning-material-images.store-chapter', $chapter), [
        'image' => UploadedFile::fake()->image('diagram.png'),
    ])->assertOk();

    $image = LearningMaterialImage::query()->firstOrFail();
    $upload->assertJson(['id' => $image->uuid]);
    Storage::disk('local')->assertExists($image->path);

    $this->actingAs($owner)->post(route('instructor.learning-materials.store', $chapter), [
        'title' => 'Image article',
        'type' => 'article',
        'content' => '<p>Read this.</p><p><img src="'.$upload->json('url').'" onerror="alert(1)"></p>',
        'is_required' => 1,
    ])->assertRedirect();

    $material = LearningMaterial::query()->firstOrFail();
    expect($material->images()->pluck('id')->all())->toBe([$image->id])
        ->and($material->content)->toContain('<img src="'.$upload->json('url').'" alt="">')
        ->and($material->content)->not->toContain('onerror');
    expect($image->fresh()->learning_material_id)->toBe($material->id);
});

test('image uploads reject foreign owners and invalid files', function (): void {
    Storage::fake('local');
    $owner = imageAuthor();
    $other = imageAuthor();
    $chapter = imageChapter($owner);

    $this->actingAs($other)->post(route('instructor.learning-material-images.store-chapter', $chapter), [
        'image' => UploadedFile::fake()->image('denied.png'),
    ])->assertForbidden();

    $this->actingAs($owner)->post(route('instructor.learning-material-images.store-chapter', $chapter), [
        'image' => UploadedFile::fake()->create('document.pdf', 10, 'application/pdf'),
    ])->assertSessionHasErrors('image');
});

test('enrolled trainees can view embedded images while unrelated users cannot', function (): void {
    Storage::fake('local');
    $owner = imageAuthor();
    $trainee = imageAuthor('trainee');
    $outsider = imageAuthor('trainee');
    $chapter = imageChapter($owner);
    $material = LearningMaterial::factory()->for($chapter, 'chapter')->create();
    $image = LearningMaterialImage::factory()->for($chapter, 'chapter')->for($material, 'material')->for($owner, 'uploader')->create();
    Storage::disk('local')->put($image->path, 'image');
    Enrollment::factory()->for($material->chapter->module->course)->for($trainee, 'trainee')->create();

    $this->actingAs($trainee)->get(route('learning-material-images.show', $image))->assertOk();
    $this->actingAs($outsider)->get(route('learning-material-images.show', $image))->assertForbidden();
});

test('removing an embedded image from a material removes its private file', function (): void {
    Storage::fake('local');
    $owner = imageAuthor();
    $chapter = imageChapter($owner);
    $material = LearningMaterial::factory()->for($chapter, 'chapter')->create();
    $image = LearningMaterialImage::factory()->for($chapter, 'chapter')->for($material, 'material')->for($owner, 'uploader')->create();
    Storage::disk('local')->put($image->path, 'image');

    $this->actingAs($owner)->put(route('instructor.learning-materials.update', $material), [
        'title' => $material->title,
        'type' => 'article',
        'content' => '<p>No image remains.</p>',
        'is_required' => 1,
    ])->assertRedirect();

    expect(LearningMaterialImage::query()->find($image->id))->toBeNull();
    Storage::disk('local')->assertMissing($image->path);
});

test('images uploaded while editing remain pending until the material is saved', function (): void {
    Storage::fake('local');
    $owner = imageAuthor();
    $chapter = imageChapter($owner);
    $material = LearningMaterial::factory()->for($chapter, 'chapter')->create();

    $upload = $this->actingAs($owner)->post(route('instructor.learning-material-images.store-material', $material), [
        'image' => UploadedFile::fake()->image('edit-image.png'),
    ])->assertOk();
    $image = LearningMaterialImage::query()->firstOrFail();

    expect($image->learning_material_id)->toBeNull();

    $this->actingAs($owner)->put(route('instructor.learning-materials.update', $material), [
        'title' => $material->title,
        'type' => 'article',
        'content' => '<p><img src="'.$upload->json('url').'" /></p>',
        'is_required' => 1,
    ])->assertRedirect();

    expect($image->fresh()->learning_material_id)->toBe($material->id)
        ->and($image->fresh()->expires_at)->toBeNull();
});
