<?php

use App\Models\Assessment;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\LearningMaterial;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->artisan('admin:permissions-sync')->assertSuccessful();
});

function applicationUser(string $role): User
{
    $user = User::factory()->create();
    $user->syncRoles([$role]);

    return $user;
}

function catalogCourse(User $instructor): array
{
    $course = Course::factory()->published()->for($instructor, 'instructor')->create();
    $module = CourseModule::factory()->for($course)->create();
    $material = LearningMaterial::factory()->for($module, 'module')->create();

    return [$course, $material];
}

test('Trainees see published courses and submit one pending application', function () {
    $instructor = applicationUser('instructor');
    $trainee = applicationUser('trainee');
    [$published] = catalogCourse($instructor);
    $draft = Course::factory()->for($instructor, 'instructor')->create();

    $this->actingAs($trainee)->get(route('learning.catalog.index'))
        ->assertOk()->assertSee($published->title)->assertDontSee($draft->title);
    $this->actingAs($trainee)->post(route('learning.applications.store', $published))
        ->assertRedirect(route('learning.applications.index'));

    $application = Enrollment::whereBelongsTo($published)->whereBelongsTo($trainee, 'trainee')->firstOrFail();
    expect($application->status->value)->toBe('pending')
        ->and($application->requested_at)->not->toBeNull()
        ->and($application->enrolled_at)->toBeNull()
        ->and(Activity::where('event', 'course-application.submitted')->where('causer_id', $trainee->id)->exists())->toBeTrue();

    $this->actingAs($trainee)->post(route('learning.applications.store', $published))->assertSessionHasErrors('course');
    expect(Enrollment::whereBelongsTo($published)->whereBelongsTo($trainee, 'trainee')->count())->toBe(1);
});

test('pending applications do not grant learning or linked assessment access', function () {
    $instructor = applicationUser('instructor');
    $trainee = applicationUser('trainee');
    [$course, $material] = catalogCourse($instructor);
    $assessment = Assessment::factory()->published()->for($instructor, 'creator')->for($course)->create();

    $this->actingAs($trainee)->post(route('learning.applications.store', $course));
    $application = Enrollment::whereBelongsTo($course)->whereBelongsTo($trainee, 'trainee')->firstOrFail();

    $this->actingAs($trainee)->get(route('learning.courses.materials.show', [$application, $material]))->assertForbidden();
    $this->actingAs($trainee)->post(route('learning.assessments.start', $assessment))->assertForbidden();
    $this->actingAs($trainee)->get(route('learning.courses.index'))->assertDontSee($course->title);
});

test('Admin approves any application and activates My Learning', function () {
    $admin = applicationUser('admin');
    $instructor = applicationUser('instructor');
    $trainee = applicationUser('trainee');
    [$course] = catalogCourse($instructor);
    $this->actingAs($trainee)->post(route('learning.applications.store', $course));
    $application = Enrollment::firstOrFail();

    $this->actingAs($admin)->patch(route('admin.applications.approve', $application))->assertRedirect();

    $application->refresh();
    expect($application->status->value)->toBe('active')
        ->and($application->reviewed_by)->toBe($admin->id)
        ->and($application->enrolled_by)->toBe($admin->id)
        ->and($application->enrolled_at)->not->toBeNull();
    $this->actingAs($trainee)->get(route('learning.courses.index'))->assertSee($course->title);
});

test('only the owning Instructor can review a course application', function () {
    $owner = applicationUser('instructor');
    $other = applicationUser('instructor');
    $trainee = applicationUser('trainee');
    [$course] = catalogCourse($owner);
    $this->actingAs($trainee)->post(route('learning.applications.store', $course));
    $application = Enrollment::firstOrFail();

    $this->actingAs($other)->get(route('instructor.applications.index'))->assertOk()->assertDontSee($course->title);
    $this->actingAs($other)->patch(route('instructor.applications.approve', $application))->assertForbidden();
    $this->actingAs($owner)->patch(route('instructor.applications.approve', $application))->assertRedirect();
    expect($application->fresh()->status->value)->toBe('active');
});

test('rejected applications show the reason and can be submitted again', function () {
    $admin = applicationUser('admin');
    $instructor = applicationUser('instructor');
    $trainee = applicationUser('trainee');
    [$course] = catalogCourse($instructor);
    $this->actingAs($trainee)->post(route('learning.applications.store', $course));
    $application = Enrollment::firstOrFail();

    $this->actingAs($admin)->patch(route('admin.applications.reject', $application), ['review_note' => 'Complete prerequisite orientation first.'])->assertRedirect();
    $this->actingAs($trainee)->get(route('learning.applications.index'))->assertSee('Complete prerequisite orientation first.');
    $this->actingAs($trainee)->post(route('learning.applications.store', $course))->assertRedirect(route('learning.applications.index'));

    $application->refresh();
    expect($application->status->value)->toBe('pending')->and($application->review_note)->toBeNull()->and($application->reviewed_by)->toBeNull();
});

test('direct administrative assignment supersedes a pending application', function () {
    $admin = applicationUser('admin');
    $instructor = applicationUser('instructor');
    $trainee = applicationUser('trainee');
    [$course] = catalogCourse($instructor);
    $this->actingAs($trainee)->post(route('learning.applications.store', $course));

    $this->actingAs($admin)->post(route('admin.enrollments.store'), ['course_id' => $course->id, 'trainees' => [$trainee->id]])->assertRedirect();

    $enrollment = Enrollment::firstOrFail();
    expect($enrollment->status->value)->toBe('active')->and($enrollment->requested_at)->toBeNull()->and($enrollment->enrolled_by)->toBe($admin->id);
});
