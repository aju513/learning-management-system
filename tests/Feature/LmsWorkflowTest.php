<?php

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AttemptAnswer;
use App\Models\Course;
use App\Models\CourseChapter;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\LearningMaterial;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->artisan('admin:permissions-sync')->assertSuccessful();
});

function portalUser(string $role): User
{
    $user = User::factory()->create();
    $user->syncRoles([Role::findByName($role)]);

    return $user;
}

function publishedCourse(User $instructor, int $materialCount = 1): array
{
    $course = Course::factory()->published()->for($instructor, 'instructor')->create(['navigation_mode' => 'sequential']);
    $module = CourseModule::factory()->for($course)->create();
    $chapter = CourseChapter::factory()->for($module, 'module')->create();
    $materials = collect();
    foreach (range(1, $materialCount) as $position) {
        $materials->push(LearningMaterial::factory()->for($chapter, 'chapter')->create(['position' => $position]));
    }

    return [$course, $module, $materials];
}

test('the four roles have strict portal-specific academic responsibilities', function () {
    expect(Role::findByName('admin')->hasPermissionTo('enrollments.create'))->toBeTrue()
        ->and(Role::findByName('admin')->hasPermissionTo('courses.create'))->toBeFalse()
        ->and(Role::findByName('instructor')->hasPermissionTo('courses.create'))->toBeTrue()
        ->and(Role::findByName('instructor')->hasPermissionTo('enrollments.create'))->toBeFalse()
        ->and(Role::findByName('trainee')->hasPermissionTo('course-applications.create'))->toBeTrue()
        ->and(Role::findByName('trainee')->hasPermissionTo('courses.create'))->toBeFalse();
});

test('staff course cards use the compact status and metadata layout', function () {
    $instructor = portalUser('instructor');
    $course = Course::factory()->for($instructor, 'instructor')->create([
        'title' => 'Staff Card Course',
        'short_description' => 'This description should not appear on the staff card.',
        'status' => 'published',
    ]);
    CourseModule::factory()->for($course)->create();

    $this->actingAs($instructor)->get(route('instructor.courses.index'))
        ->assertOk()
        ->assertSee('Staff Card Course')
        ->assertSee('published')
        ->assertSee('Created '.$course->created_at->format('M j, Y'), false)
        ->assertSee($instructor->name)
        ->assertSee('Open')
        ->assertSee('aspect-[3/2]', false)
        ->assertSee('role="link"', false)
        ->assertSee('cursor-pointer', false)
        ->assertSee('@keydown.enter.prevent', false)
        ->assertSee('group-hover:scale-105', false)
        ->assertDontSee('This description should not appear on the staff card.')
        ->assertDontSee('1 modules')
        ->assertDontSee('0 trainees');
});

test('an Instructor builds an owned course but cannot edit another instructors course', function () {
    $instructor = portalUser('instructor');
    $otherInstructor = portalUser('instructor');

    $response = $this->actingAs($instructor)->post(route('instructor.courses.store'), [
        'title' => 'Public Service Foundations',
        'short_description' => 'A foundation course for public servants.',
        'difficulty' => 'beginner',
        'estimated_duration_minutes' => 90,
        'navigation_mode' => 'sequential',
    ]);

    $course = Course::where('title', 'Public Service Foundations')->firstOrFail();
    $response->assertRedirect(route('instructor.courses.show', $course));
    expect($course->instructor_id)->toBe($instructor->id)->and($course->status->value)->toBe('draft');

    $this->actingAs($instructor)->post(route('instructor.course-modules.store', $course), ['title' => 'Introduction'])->assertRedirect();
    $module = $course->modules()->firstOrFail();
    $chapter = $module->chapters()->firstOrFail();
    $this->actingAs($instructor)->post(route('instructor.learning-materials.store', $chapter), [
        'title' => 'Welcome article', 'type' => 'article', 'content' => '<p onmouseover="alert(1)">Welcome</p><script>alert(1)</script>',
        'duration_minutes' => 5, 'is_required' => 1,
    ])->assertRedirect();

    expect($chapter->materials()->firstOrFail()->content)->toBe('<p>Welcome</p>alert(1)');
    $this->actingAs($instructor)->patch(route('instructor.courses.status', $course), ['status' => 'published'])->assertRedirect();
    expect($course->fresh()->status->value)->toBe('published')
        ->and(Activity::where('event', 'course.status-changed')->where('causer_id', $instructor->id)->exists())->toBeTrue();

    $otherCourse = Course::factory()->for($otherInstructor, 'instructor')->create();
    $this->actingAs($instructor)->get(route('instructor.courses.edit', $otherCourse))->assertForbidden();
});

test('Admin academic screens are read-only while direct enrollment remains available', function () {
    $admin = portalUser('admin');
    $instructor = portalUser('instructor');
    $trainee = portalUser('trainee');
    [$course] = publishedCourse($instructor);

    $this->actingAs($admin)->get(route('admin.courses.show', $course))
        ->assertOk()->assertDontSee('Edit details')->assertDontSee('Add module');
    $this->actingAs($admin)->post('/admin/courses', [])->assertMethodNotAllowed();

    $this->actingAs($admin)->post(route('admin.enrollments.store'), [
        'course_id' => $course->id, 'trainees' => [$trainee->id],
    ])->assertRedirect();

    expect(Enrollment::whereBelongsTo($trainee, 'trainee')->firstOrFail()->status->value)->toBe('active');
});

test('sequential learning records material and course completion', function () {
    $admin = portalUser('admin');
    $instructor = portalUser('instructor');
    $trainee = portalUser('trainee');
    [$course, , $materials] = publishedCourse($instructor, 2);

    $this->actingAs($admin)->post(route('admin.enrollments.store'), ['course_id' => $course->id, 'trainees' => [$trainee->id]])->assertRedirect();
    $enrollment = Enrollment::whereBelongsTo($trainee, 'trainee')->whereBelongsTo($course)->firstOrFail();

    $this->actingAs($trainee)->get(route('learning.courses.player', $enrollment))
        ->assertOk()
        ->assertSee('Learning space')
        ->assertSee('Course contents')
        ->assertSee('id="chapter-'.$materials[0]->chapter->id.'"', false)
        ->assertDontSee('Toggle Sidebar');
    $this->actingAs($trainee)->get(route('learning.courses.materials.show', [$enrollment, $materials[1]]))
        ->assertOk()->assertSee('Complete the previous required lesson first')->assertSee('Go to previous lesson');
    $this->actingAs($trainee)->get(route('learning.courses.materials.show', [$enrollment, $materials[0]]))->assertOk();
    $this->actingAs($trainee)->post(route('learning.courses.materials.complete', [$enrollment, $materials[0]]))->assertRedirect();
    expect((float) $enrollment->fresh()->progress_percentage)->toBe(50.0);

    $this->actingAs($trainee)->post(route('learning.courses.materials.complete', [$enrollment, $materials[1]]))->assertRedirect();
    expect($enrollment->fresh()->status->value)->toBe('completed')->and((float) $enrollment->fresh()->progress_percentage)->toBe(100.0);
});

test('completed course launch opens the review summary instead of the first lesson', function () {
    $admin = portalUser('admin');
    $instructor = portalUser('instructor');
    $trainee = portalUser('trainee');
    [$course, , $materials] = publishedCourse($instructor, 2);

    $this->actingAs($admin)->post(route('admin.enrollments.store'), ['course_id' => $course->id, 'trainees' => [$trainee->id]])->assertRedirect();
    $enrollment = Enrollment::whereBelongsTo($trainee, 'trainee')->whereBelongsTo($course)->firstOrFail();
    foreach ($materials as $material) {
        $enrollment->materialProgress()->create(['learning_material_id' => $material->id, 'last_viewed_at' => now(), 'completed_at' => now()]);
    }
    $enrollment->update(['status' => 'completed', 'progress_percentage' => 100, 'completed_at' => now()]);

    $this->actingAs($trainee)->get(route('learning.courses.player', $enrollment))
        ->assertRedirect(route('learning.courses.summary', $enrollment));
    $this->actingAs($trainee)->get(route('learning.courses.summary', $enrollment))
        ->assertOk()->assertSee('Course completed')->assertSee('Start from beginning');
});

test('objective assessments enforce attempts and grade correct answers', function () {
    $instructor = portalUser('instructor');
    $trainee = portalUser('trainee');
    [$course] = publishedCourse($instructor);
    Enrollment::factory()->for($course)->for($trainee, 'trainee')->create();
    $assessment = Assessment::factory()->for($instructor, 'creator')->create();

    $this->actingAs($instructor)->post(route('instructor.assessment-questions.store', $assessment), [
        'prompt' => 'Which answer is correct?', 'type' => 'single_choice', 'marks' => 2,
        'options' => ['First', 'Second', 'Third', 'Fourth'], 'correct_options' => [1],
    ])->assertRedirect();
    $this->actingAs($instructor)->patch(route('instructor.assessments.status', $assessment), ['status' => 'published'])->assertRedirect();
    $this->actingAs($instructor)->get(route('instructor.assessments.show', $assessment))
        ->assertSee('Questions are locked because this quiz is published.')
        ->assertDontSee('name="prompt"', false);
    $this->actingAs($instructor)->post(route('instructor.assessment-questions.store', $assessment), [
        'prompt' => 'A locked question', 'type' => 'single_choice', 'marks' => 1,
        'options' => ['First', 'Second'], 'correct_options' => [0],
    ])->assertSessionHasErrors('question');
    $this->actingAs($instructor)->post(route('instructor.assessment-assignments.store', $assessment), ['trainees' => [$trainee->id]])->assertRedirect();

    $this->actingAs($trainee)->post(route('learning.assessments.start', $assessment))->assertRedirect();
    $attempt = AssessmentAttempt::whereBelongsTo($trainee, 'trainee')->firstOrFail();
    $question = $assessment->questions()->with('options')->firstOrFail();
    $correctOption = $question->options->firstWhere('is_correct', true);
    $this->actingAs($trainee)->patchJson(route('learning.assessments.attempts.answers.save', $attempt), [
        'answers' => [$question->id => [$correctOption->id]],
    ])->assertOk()->assertJson(['saved' => true]);
    expect(AttemptAnswer::where('assessment_attempt_id', $attempt->id)->count())->toBe(1);
    $this->actingAs($trainee)->post(route('learning.assessments.attempts.submit', $attempt), [
        'answers' => [$question->id => [$correctOption->id]],
    ])->assertRedirect(route('learning.assessments.attempts.show', $attempt));
    $this->actingAs($trainee)->post(route('learning.assessments.attempts.submit', $attempt), [
        'answers' => [$question->id => [$correctOption->id]],
    ])->assertRedirect(route('learning.assessments.attempts.show', $attempt));

    expect($attempt->fresh()->passed)->toBeTrue()
        ->and((float) $attempt->fresh()->score_percentage)->toBe(100.0)
        ->and(AttemptAnswer::where('assessment_attempt_id', $attempt->id)->count())->toBe(1);
});

test('Instructor assessment assignment is limited to trainees related through owned courses', function () {
    $instructor = portalUser('instructor');
    $related = portalUser('trainee');
    $unrelated = portalUser('trainee');
    [$course] = publishedCourse($instructor);
    Enrollment::factory()->for($course)->for($related, 'trainee')->create();
    $assessment = Assessment::factory()->for($instructor, 'creator')->create();

    $this->actingAs($instructor)->post(route('instructor.assessment-assignments.store', $assessment), [
        'trainees' => [$unrelated->id],
    ])->assertSessionHasErrors('trainees');

    $this->actingAs($instructor)->post(route('instructor.assessment-assignments.store', $assessment), [
        'trainees' => [$related->id],
    ])->assertRedirect();
});

test('role-specific LMS screens render and exclude foreign functions', function () {
    $admin = portalUser('admin');
    $instructor = portalUser('instructor');
    $trainee = portalUser('trainee');

    foreach ([route('admin.dashboard'), route('admin.instructors.index'), route('admin.trainees.index'), route('admin.courses.index'), route('admin.applications.index'), route('admin.enrollments.index'), route('admin.assessments.index'), route('admin.results.index'), route('admin.reports.index')] as $route) {
        $this->actingAs($admin)->get($route)->assertOk();
    }
    foreach ([route('instructor.dashboard'), route('instructor.courses.index'), route('instructor.applications.index'), route('instructor.trainees.index'), route('instructor.assessments.index'), route('instructor.results.index')] as $route) {
        $this->actingAs($instructor)->get($route)->assertOk();
    }
    foreach ([route('learning.dashboard'), route('learning.catalog.index'), route('learning.applications.index'), route('learning.courses.index'), route('learning.assessments.index'), route('learning.results.index')] as $route) {
        $this->actingAs($trainee)->get($route)->assertOk();
    }
});
