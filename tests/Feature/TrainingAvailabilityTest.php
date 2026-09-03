<?php

use App\Models\Assessment;
use App\Models\Course;
use App\Models\CourseAssessment;
use App\Models\CourseChapter;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\LearningMaterial;
use App\Models\User;
use App\Services\NavigationService;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->artisan('admin:permissions-sync')->assertSuccessful();
});

function trainingAvailabilityUser(string $role): User
{
    $user = User::factory()->create();
    $user->syncRoles([Role::findByName($role)]);

    return $user;
}

function trainingCatalogCourse(User $instructor, array $attributes = []): Course
{
    $course = Course::factory()->published()->for($instructor, 'instructor')->create($attributes);
    $module = CourseModule::factory()->for($course)->create();
    $chapter = CourseChapter::factory()->for($module, 'module')->create();
    LearningMaterial::factory()->for($chapter, 'chapter')->create();

    return $course;
}

test('course and standalone assessment authoring requires a training when availability is restricted', function () {
    $instructor = trainingAvailabilityUser('instructor');

    $this->actingAs($instructor)->post(route('instructor.courses.store'), [
        'title' => 'Restricted course', 'short_description' => 'A restricted course.',
        'difficulty' => 'beginner', 'estimated_duration_minutes' => 60, 'navigation_mode' => 'free',
        'available_to_all' => 0,
    ])->assertSessionHasErrors('required_training_key');

    $this->actingAs($instructor)->post(route('instructor.assessments.store'), [
        'title' => 'Restricted test', 'duration_minutes' => 30, 'passing_percentage' => 60,
        'max_attempts' => 1, 'show_results' => 1, 'available_to_all' => 0,
    ])->assertSessionHasErrors('required_training_key');

    $this->actingAs($instructor)->post(route('instructor.courses.store'), [
        'title' => 'Restricted course', 'short_description' => 'A restricted course.',
        'difficulty' => 'beginner', 'estimated_duration_minutes' => 60, 'navigation_mode' => 'free',
        'available_to_all' => 0, 'required_training_key' => 'leadership-foundations',
    ])->assertRedirect();

    expect(Course::where('title', 'Restricted course')->firstOrFail()->availability_scope->value)->toBe('training');

    $this->actingAs($instructor)->post(route('instructor.assessments.store'), [
        'title' => 'Restricted test', 'duration_minutes' => 30, 'passing_percentage' => 60,
        'max_attempts' => 1, 'show_results' => 1, 'available_to_all' => 0,
        'required_training_key' => 'leadership-foundations',
    ])->assertRedirect();

    expect(Assessment::where('title', 'Restricted test')->firstOrFail()->availability_scope->value)->toBe('training');
});

test('restricted courses are hidden until the trainee is enrolled in the configured training', function () {
    $instructor = trainingAvailabilityUser('instructor');
    $trainee = trainingAvailabilityUser('trainee');
    $course = trainingCatalogCourse($instructor, [
        'availability_scope' => 'training',
        'required_training_key' => 'leadership-foundations',
    ]);

    $this->actingAs($trainee)->get(route('learning.catalog.index'))
        ->assertOk()->assertDontSee($course->title);
    $this->actingAs($trainee)->get(route('learning.catalog.show', $course))->assertForbidden();
    $this->actingAs($trainee)->post(route('learning.applications.store', $course))->assertForbidden();

    config(['training.enrollments.'.((string) $trainee->id) => ['leadership-foundations']]);

    $this->actingAs($trainee)->get(route('learning.catalog.index'))
        ->assertOk()->assertSee($course->title);
    $this->actingAs($trainee)->post(route('learning.applications.store', $course))
        ->assertRedirect(route('learning.applications.index'));
});

test('standalone assessments are filtered and blocked by training eligibility', function () {
    $instructor = trainingAvailabilityUser('instructor');
    $trainee = trainingAvailabilityUser('trainee');
    $assessment = Assessment::factory()->published()->for($instructor, 'creator')->create([
        'availability_scope' => 'training',
        'required_training_key' => 'project-management-basics',
    ]);
    $assessment->assignments()->create([
        'user_id' => $trainee->id,
        'assigned_by' => $instructor->id,
        'assigned_at' => now(),
    ]);

    $this->actingAs($trainee)->get(route('learning.assessments.index'))
        ->assertOk()->assertSee($assessment->title)->assertSee('Ready');
    $this->actingAs($trainee)->post(route('learning.assessments.start', $assessment))->assertRedirect();

    config(['training.enrollments.'.((string) $trainee->id) => ['project-management-basics']]);

    $this->actingAs($trainee)->get(route('learning.assessments.index'))
        ->assertOk()->assertSee($assessment->title);
});

test('course assessments inherit the parent course training restriction', function () {
    $instructor = trainingAvailabilityUser('instructor');
    $trainee = trainingAvailabilityUser('trainee');
    $course = Course::factory()->published()->for($instructor, 'instructor')->create([
        'availability_scope' => 'training',
        'required_training_key' => 'workplace-safety',
    ]);
    $module = CourseModule::factory()->for($course)->create();
    $chapter = CourseChapter::factory()->for($module, 'module')->create();
    $material = LearningMaterial::factory()->for($chapter, 'chapter')->create(['type' => 'course_assessment']);
    $assessment = CourseAssessment::create(['learning_material_id' => $material->id]);
    $enrollment = Enrollment::factory()->for($course)->for($trainee, 'trainee')->create(['status' => 'active']);

    $this->actingAs($trainee)->post(route('learning.courses.materials.course-assessment.start', [$enrollment, $material]))->assertForbidden();

    config(['training.enrollments.'.((string) $trainee->id) => ['workplace-safety']]);

    $this->actingAs($trainee)->post(route('learning.courses.materials.course-assessment.start', [$enrollment, $material]))
        ->assertSessionHasErrors('assessment');
});

test('trainee navigation keeps overview and groups eligible courses and tests by training', function () {
    $instructor = trainingAvailabilityUser('instructor');
    $trainee = trainingAvailabilityUser('trainee');
    config(['training.enrollments.'.((string) $trainee->id) => ['leadership-foundations']]);

    $course = trainingCatalogCourse($instructor, [
        'title' => 'Leadership Overview Course',
        'availability_scope' => 'training',
        'required_training_key' => 'leadership-foundations',
    ]);
    $hiddenCourse = trainingCatalogCourse($instructor, [
        'title' => 'Safety Overview Course',
        'availability_scope' => 'training',
        'required_training_key' => 'workplace-safety',
    ]);
    $assessment = Assessment::factory()->published()->for($instructor, 'creator')->create([
        'title' => 'Leadership Overview Test',
        'availability_scope' => 'training',
        'required_training_key' => 'leadership-foundations',
    ]);
    $assessment->assignments()->create(['user_id' => $trainee->id, 'assigned_by' => $instructor->id, 'assigned_at' => now()]);

    $this->actingAs($trainee)->get(route('learning.dashboard'))
        ->assertOk()
        ->assertSee($course->title)
        ->assertSee($assessment->title)
        ->assertSee('Leadership Foundations')
        ->assertSee('Featured Courses')
        ->assertSee('Explore by Category')
        ->assertSee('aria-current="page"', false)
        ->assertDontSee($hiddenCourse->title)
        ->assertSee('Overview');

    $navigation = app(NavigationService::class)->forUser($trainee);

    expect($navigation)->toHaveCount(4)
        ->and($navigation[0]['key'])->toBe('overview')
        ->and($navigation[1]['key'])->toBe('courses')
        ->and(collect($navigation[1]['children'])->pluck('label')->all())->toBe(['Course Catalog', 'Applied Courses', 'Enrolled Courses'])
        ->and($navigation[2]['key'])->toBe('tests')
        ->and(collect($navigation[2]['children'])->pluck('label')->all())->toBe(['Test Catalog', 'My Tests'])
        ->and($navigation[3]['key'])->toBe('credit-scores');
});

test('trainee overview course cards show shared enrollment progress', function () {
    $instructor = trainingAvailabilityUser('instructor');
    $trainee = trainingAvailabilityUser('trainee');
    $course = trainingCatalogCourse($instructor, ['title' => 'Progress Card Course']);
    $chapter = $course->modules()->firstOrFail()->chapters()->firstOrFail();
    $secondMaterial = LearningMaterial::factory()->for($chapter, 'chapter')->create(['position' => 2]);
    $firstMaterial = $chapter->materials()->whereKeyNot($secondMaterial->id)->firstOrFail();
    $enrollment = Enrollment::factory()->for($course)->for($trainee, 'trainee')->create(['status' => 'active']);
    $enrollment->materialProgress()->create([
        'learning_material_id' => $firstMaterial->id,
        'last_viewed_at' => now(),
        'completed_at' => now(),
    ]);

    $this->actingAs($trainee)->get(route('learning.dashboard'))
        ->assertOk()
        ->assertSee('Progress Card Course')
        ->assertSee('2 Lessons')
        ->assertSee('50% learned')
        ->assertSee('Continue learning');
});

test('trainee test navigation separates assigned and started tests', function () {
    $instructor = trainingAvailabilityUser('instructor');
    $trainee = trainingAvailabilityUser('trainee');
    $assessment = Assessment::factory()->published()->for($instructor, 'creator')->create(['title' => 'Assigned Safety Test']);
    $assessment->assignments()->create(['user_id' => $trainee->id, 'assigned_by' => $instructor->id, 'assigned_at' => now()]);

    $this->actingAs($trainee)->get(route('learning.assessments.applied'))
        ->assertOk()->assertSee('Assigned Safety Test')->assertSee('Applied Tests');
    $this->actingAs($trainee)->get(route('learning.assessments.index'))
        ->assertOk()->assertSee('Assigned Safety Test')->assertSee('Enrolled Tests');

    $assessment->attempts()->create([
        'user_id' => $trainee->id,
        'attempt_number' => 1,
        'status' => 'in_progress',
        'started_at' => now(),
        'total_marks' => 10,
    ]);

    $this->actingAs($trainee)->get(route('learning.assessments.applied'))
        ->assertOk()->assertDontSee('Assigned Safety Test');
    $this->actingAs($trainee)->get(route('learning.assessments.index'))
        ->assertOk()->assertSee('Assigned Safety Test')->assertSee('Enrolled Tests');
});
