<?php

use App\Models\Assessment;
use App\Models\Course;
use App\Models\CourseAssessment;
use App\Models\CourseChapter;
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
    $chapter = CourseChapter::factory()->for($module, 'module')->create();
    $material = LearningMaterial::factory()->for($chapter, 'chapter')->create();

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

test('My Learning excludes active enrollments for draft courses and identifies enrollment URLs', function () {
    $instructor = applicationUser('instructor');
    $trainee = applicationUser('trainee');
    [$published, $publishedMaterial] = catalogCourse($instructor);
    [$draft, $draftMaterial] = catalogCourse($instructor);
    $draft->update(['status' => 'draft', 'published_at' => null]);

    $publishedEnrollment = Enrollment::factory()->for($published)->for($trainee, 'trainee')->create(['status' => 'active']);
    $draftEnrollment = Enrollment::factory()->for($draft)->for($trainee, 'trainee')->create(['status' => 'active']);

    $response = $this->actingAs($trainee)->get(route('learning.courses.index'));
    $response->assertOk()->assertSee($published->title)->assertDontSee($draft->title);

    $player = $this->actingAs($trainee)->get(route('learning.courses.player', $publishedEnrollment));
    $player->assertOk()->assertSee(route('learning.courses.materials.show', [$publishedEnrollment, $publishedMaterial]), false);

    $this->actingAs($trainee)->get(route('learning.courses.player', $draftEnrollment))->assertForbidden();
    $this->actingAs($trainee)->get(route('learning.courses.materials.show', [$publishedEnrollment, $draftMaterial]))->assertForbidden();
});

test('trainees can search, filter, and sort My Courses server-side', function () {
    $instructor = applicationUser('instructor');
    $trainee = applicationUser('trainee');
    [$inProgress] = catalogCourse($instructor);
    $inProgress->update(['title' => 'Alpha Governance']);
    [$completed] = catalogCourse($instructor);
    $completed->update(['title' => 'Completed Policy']);
    [$notStarted] = catalogCourse($instructor);
    $notStarted->update(['title' => 'Zeta Leadership']);

    Enrollment::factory()->for($inProgress)->for($trainee, 'trainee')->create(['status' => 'active', 'progress_percentage' => 35]);
    Enrollment::factory()->for($completed)->for($trainee, 'trainee')->create(['status' => 'completed', 'progress_percentage' => 100]);
    Enrollment::factory()->for($notStarted)->for($trainee, 'trainee')->create(['status' => 'active', 'progress_percentage' => 0]);

    $this->actingAs($trainee)->get(route('learning.courses.index', ['status' => 'in_progress']))
        ->assertOk()->assertSee('Alpha Governance')->assertDontSee('Completed Policy')->assertDontSee('Zeta Leadership');

    $this->actingAs($trainee)->get(route('learning.courses.index', ['search' => 'Policy']))
        ->assertOk()->assertSee('Completed Policy')->assertDontSee('Alpha Governance');

    $response = $this->actingAs($trainee)->get(route('learning.courses.index', ['sort' => 'title']));
    $response->assertOk()->assertSee('Course Name')->assertSee('Alpha Governance');
    expect(strpos($response->getContent(), 'Alpha Governance'))->toBeLessThan(strpos($response->getContent(), 'Zeta Leadership'));
});

test('My Courses rejects unsupported filter values', function () {
    $trainee = applicationUser('trainee');

    $this->actingAs($trainee)->get(route('learning.courses.index', ['status' => 'unknown']))
        ->assertSessionHasErrors('status');
});

test('catalog labels course assessment materials consistently and assignment selects show course titles', function () {
    $admin = applicationUser('admin');
    $instructor = applicationUser('instructor');
    $trainee = applicationUser('trainee');
    [$course, $material] = catalogCourse($instructor);
    $material->update(['type' => 'article', 'title' => 'Good Governance Knowledge Check']);
    CourseAssessment::create(['learning_material_id' => $material->id]);

    $this->actingAs($trainee)->get(route('learning.catalog.show', $course))
        ->assertOk()
        ->assertSee('Good Governance Knowledge Check')
        ->assertSee('Course assessment')
        ->assertDontSee('course_assessment');

    $this->actingAs($admin)->get(route('admin.enrollments.index'))
        ->assertOk()->assertSee($course->title);
});

test('catalog formats escaped description line breaks as separate paragraphs', function () {
    $instructor = applicationUser('instructor');
    $trainee = applicationUser('trainee');
    [$course] = catalogCourse($instructor);
    $course->update(['description' => 'First paragraph\\n\\nSecond paragraph']);

    $this->actingAs($trainee)->get(route('learning.catalog.show', $course))
        ->assertOk()
        ->assertSee('<p class="whitespace-pre-line">First paragraph</p>', false)
        ->assertSee('<p class="whitespace-pre-line">Second paragraph</p>', false)
        ->assertDontSee('First paragraph\\n\\nSecond paragraph', false);
});

test('pending applications do not grant learning or standalone quiz access', function () {
    $instructor = applicationUser('instructor');
    $trainee = applicationUser('trainee');
    [$course, $material] = catalogCourse($instructor);
    $assessment = Assessment::factory()->published()->for($instructor, 'creator')->create();

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

test('instructor applications are grouped by course and accepted trainees move to My Trainees', function () {
    $instructor = applicationUser('instructor');
    $firstTrainee = applicationUser('trainee');
    $secondTrainee = applicationUser('trainee');
    [$course] = catalogCourse($instructor);
    $secondCourse = catalogCourse($instructor)[0];

    $this->actingAs($firstTrainee)->post(route('learning.applications.store', $course));
    $firstApplication = Enrollment::whereBelongsTo($course)->whereBelongsTo($firstTrainee, 'trainee')->firstOrFail();
    $firstApplication->update(['requested_at' => now()->subDay()]);
    $this->actingAs($secondTrainee)->post(route('learning.applications.store', $course));
    $secondApplication = Enrollment::whereBelongsTo($course)->whereBelongsTo($secondTrainee, 'trainee')->firstOrFail();
    $secondApplication->update(['requested_at' => now()]);

    $response = $this->actingAs($instructor)->get(route('instructor.applications.index'));
    $response->assertOk()
        ->assertSee($course->title)
        ->assertSee('Applied 2')
        ->assertSee('Accepted 0')
        ->assertSee('bi-plus', false)
        ->assertSee('bi-dash', false);
    expect($response->getContent())->toContain($firstTrainee->name);
    expect(strpos($response->getContent(), $firstTrainee->name))->toBeLessThan(strpos($response->getContent(), $secondTrainee->name));

    $this->actingAs($instructor)->patch(route('instructor.applications.approve', $firstApplication))->assertRedirect();

    $this->actingAs($instructor)->get(route('instructor.applications.index'))
        ->assertDontSee($firstTrainee->name)
        ->assertSee($secondTrainee->name)
        ->assertSee('Applied 1')
        ->assertSee('Accepted 1');
    $this->actingAs($instructor)->get(route('instructor.trainees.index'))
        ->assertSee($course->title)
        ->assertSee($firstTrainee->name)
        ->assertSee('Accepted 1')
        ->assertSee('bi-plus', false)
        ->assertSee('bi-dash', false);
    $this->actingAs($instructor)->get(route('instructor.applications.index'))
        ->assertDontSee('aria-controls="application-course-'.$secondCourse->id.'"', false);
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
