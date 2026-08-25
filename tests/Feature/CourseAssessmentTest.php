<?php

use App\Models\Course;
use App\Models\CourseAssessment;
use App\Models\CourseAssessmentAttempt;
use App\Models\CourseAssessmentQuestion;
use App\Models\CourseChapter;
use App\Models\CourseModule;
use App\Models\CreditAward;
use App\Models\Enrollment;
use App\Models\FiscalYear;
use App\Models\LearningMaterial;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->artisan('admin:permissions-sync')->assertSuccessful();
});

function assessmentPortalUser(string $role): User
{
    $user = User::factory()->create();
    $user->syncRoles([Role::findByName($role)]);

    return $user;
}

function courseAssessmentMaterial(User $instructor, array $courseAttributes = []): array
{
    $course = Course::factory()->published()->for($instructor, 'instructor')->create(['navigation_mode' => 'sequential', ...$courseAttributes]);
    $module = CourseModule::factory()->for($course)->create();
    $chapter = CourseChapter::factory()->for($module, 'module')->create();
    $material = LearningMaterial::factory()->for($chapter, 'chapter')->create([
        'type' => 'course_assessment',
        'content' => '<p>Complete the knowledge check.</p>',
        'position' => 1,
    ]);
    $assessment = CourseAssessment::create(['learning_material_id' => $material->id, 'passing_percentage' => 60]);
    $next = LearningMaterial::factory()->for($chapter, 'chapter')->create(['position' => 2]);

    return [$course, $material, $assessment, $next];
}

test('instructors can author only choice-based course assessment questions', function () {
    $instructor = assessmentPortalUser('instructor');
    [$course, , $assessment] = courseAssessmentMaterial($instructor);

    $assessmentPage = $this->actingAs($instructor)->get(route('instructor.course-assessments.show', $assessment));
    $assessmentPage->assertOk()
        ->assertSee('Multiple-choice questions')
        ->assertSee('Add question')
        ->assertDontSee('Questions are locked because trainees have already started this assessment.')
        ->assertSee(route('instructor.courses.show', $course).'#chapter-'.$assessment->material->chapter->id, false);

    $response = $this->actingAs($instructor)->post(route('instructor.course-assessment-questions.store', $assessment), [
        'prompt' => 'Which answers are valid?',
        'type' => 'multiple_choice',
        'marks' => 2,
        'options' => ['First', 'Second', 'Third'],
        'correct_options' => [0, 2],
    ]);

    $response->assertRedirect();
    $question = CourseAssessmentQuestion::with('options')->firstOrFail();
    expect($question->type->value)->toBe('multiple_choice')
        ->and($question->options->where('is_correct', true)->pluck('option_text')->all())->toBe(['First', 'Third']);

    $this->actingAs($instructor)->post(route('instructor.course-assessment-questions.store', $assessment), [
        'prompt' => 'Invalid question', 'type' => 'single_choice', 'marks' => 1,
        'options' => ['Only one'], 'correct_options' => [0],
    ])->assertSessionHasErrors('options');
});

test('course assessment failures can be retaken and passing completes the required material', function () {
    $instructor = assessmentPortalUser('instructor');
    $trainee = assessmentPortalUser('trainee');
    [$course, $material, $assessment, $next] = courseAssessmentMaterial($instructor);
    $this->actingAs($instructor)->post(route('instructor.course-assessment-questions.store', $assessment), [
        'prompt' => 'What is correct?', 'type' => 'single_choice', 'marks' => 1,
        'options' => ['Correct', 'Incorrect'], 'correct_options' => [0],
    ])->assertRedirect();
    $question = $assessment->questions()->with('options')->firstOrFail();
    $correct = $question->options->firstWhere('is_correct', true);
    $wrong = $question->options->firstWhere('is_correct', false);
    $enrollment = Enrollment::factory()->for($course)->for($trainee, 'trainee')->create(['status' => 'active']);

    $this->actingAs($trainee)->get(route('learning.courses.materials.show', [$enrollment, $next]))
        ->assertOk()->assertSee('Complete the previous required lesson first')->assertSee('Go to previous lesson');
    $this->actingAs($trainee)->post(route('learning.courses.materials.course-assessment.start', [$enrollment, $material]))
        ->assertRedirect();
    $attempt = CourseAssessmentAttempt::firstOrFail();

    $this->actingAs($instructor)->get(route('instructor.course-assessments.show', $assessment))
        ->assertSee('Questions are locked because 1 attempt(s) have started this assessment.')
        ->assertDontSee('name="prompt"', false);

    $this->actingAs($trainee)->post(route('learning.course-assessment-attempts.submit', [$enrollment, $attempt]), [
        'answers' => [$question->id => $wrong->id],
    ])->assertRedirect();
    expect($attempt->refresh()->passed)->toBeFalse();
    $this->actingAs($trainee)->get(route('learning.course-assessment-attempts.show', [$enrollment, $attempt]))
        ->assertOk()->assertSee('Correct answer: Correct');

    $this->actingAs($trainee)->post(route('learning.courses.materials.course-assessment.start', [$enrollment, $material]))
        ->assertRedirect();
    $secondAttempt = CourseAssessmentAttempt::whereKeyNot($attempt->id)->firstOrFail();
    $this->actingAs($trainee)->post(route('learning.course-assessment-attempts.submit', [$enrollment, $secondAttempt]), [
        'answers' => [$question->id => $correct->id],
    ])->assertRedirect();

    expect($secondAttempt->refresh()->passed)->toBeTrue()
        ->and($enrollment->refresh()->materialProgress()->where('learning_material_id', $material->id)->first()->completed_at)->not->toBeNull();
    $this->actingAs($trainee)->get(route('learning.courses.materials.show', [$enrollment, $next]))->assertOk();
});

test('course assessment submission returns a retry-safe JSON redirect', function () {
    $instructor = assessmentPortalUser('instructor');
    $trainee = assessmentPortalUser('trainee');
    [, $material, $assessment] = courseAssessmentMaterial($instructor);
    $this->actingAs($instructor)->post(route('instructor.course-assessment-questions.store', $assessment), [
        'prompt' => 'What is correct?', 'type' => 'single_choice', 'marks' => 1,
        'options' => ['Correct', 'Incorrect'], 'correct_options' => [0],
    ])->assertRedirect();
    $question = $assessment->questions()->with('options')->firstOrFail();
    $enrollment = Enrollment::factory()->for($assessment->material->chapter->module->course)->for($trainee, 'trainee')->create(['status' => 'active']);

    $this->actingAs($trainee)->post(route('learning.courses.materials.course-assessment.start', [$enrollment, $material]))->assertRedirect();
    $attempt = CourseAssessmentAttempt::firstOrFail();
    $response = $this->actingAs($trainee)->postJson(route('learning.course-assessment-attempts.submit', [$enrollment, $attempt]), [
        'answers' => [$question->id => $question->options->first()->id],
    ]);

    $response->assertOk()->assertJsonPath('submitted', true)->assertJsonPath('redirect', route('learning.course-assessment-attempts.show', [$enrollment, $attempt]));
    $this->actingAs($trainee)->postJson(route('learning.course-assessment-attempts.submit', [$enrollment, $attempt]), [
        'answers' => [$question->id => $question->options->first()->id],
    ])->assertOk()->assertJsonPath('submitted', true);
    expect(CourseAssessmentAttempt::count())->toBe(1);
});

test('a passed course assessment prompts the trainee to claim the course credit score', function () {
    $instructor = assessmentPortalUser('instructor');
    $trainee = assessmentPortalUser('trainee');
    FiscalYear::factory()->create(['starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'status' => 'active']);
    [$course, $material, $assessment, $next] = courseAssessmentMaterial($instructor, ['credit_points' => 7.5]);
    $next->update(['is_required' => false]);
    $this->actingAs($instructor)->post(route('instructor.course-assessment-questions.store', $assessment), [
        'prompt' => 'What is correct?', 'type' => 'single_choice', 'marks' => 1,
        'options' => ['Correct', 'Incorrect'], 'correct_options' => [0],
    ])->assertRedirect();
    $question = $assessment->questions()->with('options')->firstOrFail();
    $enrollment = Enrollment::factory()->for($course)->for($trainee, 'trainee')->create(['status' => 'active']);

    $this->actingAs($trainee)->post(route('learning.courses.materials.course-assessment.start', [$enrollment, $material]))->assertRedirect();
    $attempt = CourseAssessmentAttempt::firstOrFail();
    $this->actingAs($trainee)->post(route('learning.course-assessment-attempts.submit', [$enrollment, $attempt]), [
        'answers' => [$question->id => $question->options->first()->id],
    ])->assertRedirect();

    $award = CreditAward::query()->where('user_id', $trainee->id)->where('source_key', 'course:'.$course->id)->firstOrFail();
    $this->actingAs($trainee)->get(route('learning.course-assessment-attempts.show', [$enrollment, $attempt]))
        ->assertOk()
        ->assertSee('Course credit score')
        ->assertSee('+7.50 credits')
        ->assertSee('Claim 7.50 credits')
        ->assertSee(route('learning.credit-scores.claim', $award), false);

    $this->actingAs($trainee)->post(route('learning.credit-scores.claim', $award))->assertRedirect();
    $this->actingAs($trainee)->get(route('learning.courses.summary', $enrollment))
        ->assertOk()
        ->assertSee('+7.50 credits')
        ->assertSee('This credit score has already been claimed.');
});

test('course assessment question authoring is owned by the course instructor', function () {
    $owner = assessmentPortalUser('instructor');
    $other = assessmentPortalUser('instructor');
    [, , $assessment] = courseAssessmentMaterial($owner);

    $this->actingAs($other)->get(route('instructor.course-assessments.show', $assessment))->assertForbidden();
});
