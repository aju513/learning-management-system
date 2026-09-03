<?php

use App\Enums\AssessmentApplicationStatus;
use App\Models\Assessment;
use App\Models\AssessmentApplication;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->artisan('admin:permissions-sync')->assertSuccessful();
});

function testFlowUser(string $role): User
{
    $user = User::factory()->create();
    $user->syncRoles([Role::findByName($role)]);

    return $user;
}

test('trainee applies without receiving test access and sees the request in My Tests', function (): void {
    $trainee = testFlowUser('trainee');
    $assessment = Assessment::factory()->published()->create(['title' => 'Public Service Test']);

    $this->actingAs($trainee)->post(route('learning.test-applications.store', $assessment))
        ->assertRedirect(route('learning.assessments.index'));

    $application = AssessmentApplication::query()->whereBelongsTo($assessment)->where('user_id', $trainee->id)->firstOrFail();
    expect($application->status)->toBe(AssessmentApplicationStatus::Pending)
        ->and($assessment->assignments()->where('user_id', $trainee->id)->exists())->toBeFalse();

    $this->actingAs($trainee)->get(route('learning.assessments.index'))
        ->assertOk()->assertSee('Public Service Test')->assertSee('Application Pending');
    $this->actingAs($trainee)->post(route('learning.assessments.start', $assessment))->assertForbidden();
});

test('staff application queues are scoped and render review controls', function (): void {
    $admin = testFlowUser('admin');
    $owner = testFlowUser('instructor');
    $other = testFlowUser('instructor');
    $trainee = testFlowUser('trainee');
    $assessment = Assessment::factory()->published()->for($owner, 'creator')->create(['title' => 'Queue Test']);
    $application = AssessmentApplication::factory()->for($assessment)->for($trainee, 'trainee')->create();

    $this->actingAs($admin)->get(route('admin.test-applications.index'))
        ->assertOk()->assertSee('Queue Test')->assertSee('Approve');
    $this->actingAs($owner)->get(route('instructor.test-applications.index'))
        ->assertOk()->assertSee('Queue Test');
    $this->actingAs($other)->get(route('instructor.test-applications.index'))
        ->assertOk()->assertDontSee('Queue Test');
    $this->actingAs($trainee)->get(route('admin.test-applications.index'))->assertForbidden();
});

test('admin approval creates an assignment using the test end date', function (): void {
    $admin = testFlowUser('admin');
    $trainee = testFlowUser('trainee');
    $endsAt = now()->addDays(5)->seconds(0);
    $assessment = Assessment::factory()->published()->create(['ends_at' => $endsAt]);
    $application = AssessmentApplication::factory()->for($assessment)->for($trainee, 'trainee')->create();

    $this->actingAs($admin)->patch(route('admin.test-applications.approve', $application))
        ->assertRedirect();

    expect($application->fresh()->status)->toBe(AssessmentApplicationStatus::Approved);
    $assignment = $assessment->assignments()->where('user_id', $trainee->id)->firstOrFail();
    expect($assignment->assigned_by)->toBe($admin->id)
        ->and($assignment->due_at->format('Y-m-d H:i:s'))->toBe($endsAt->format('Y-m-d H:i:s'));
});

test('owning instructor can review any eligible applicant but another instructor cannot', function (): void {
    $owner = testFlowUser('instructor');
    $other = testFlowUser('instructor');
    $trainee = testFlowUser('trainee');
    $assessment = Assessment::factory()->published()->for($owner, 'creator')->create(['title' => 'Owner Test']);
    $application = AssessmentApplication::factory()->for($assessment)->for($trainee, 'trainee')->create();

    $this->actingAs($other)->patch(route('instructor.test-applications.approve', $application))->assertForbidden();
    $this->actingAs($owner)->patch(route('instructor.test-applications.approve', $application))->assertRedirect();

    expect($assessment->assignments()->where('user_id', $trainee->id)->exists())->toBeTrue();
});

test('rejected and cancelled applications can be submitted again', function (): void {
    $admin = testFlowUser('admin');
    $trainee = testFlowUser('trainee');
    $assessment = Assessment::factory()->published()->create();
    $application = AssessmentApplication::factory()->for($assessment)->for($trainee, 'trainee')->create();

    $this->actingAs($admin)->patch(route('admin.test-applications.reject', $application), ['review_note' => 'Review the material first.'])
        ->assertRedirect();
    expect($application->fresh()->status)->toBe(AssessmentApplicationStatus::Rejected)
        ->and($application->fresh()->review_note)->toBe('Review the material first.');

    $this->actingAs($trainee)->post(route('learning.test-applications.store', $assessment))->assertRedirect();
    expect($application->fresh()->status)->toBe(AssessmentApplicationStatus::Pending)
        ->and($application->fresh()->review_note)->toBeNull();
});

test('My Tests keeps closed history and hides unreleased results', function (): void {
    $trainee = testFlowUser('trainee');
    $assessment = Assessment::factory()->create(['title' => 'Closed Confidential Test', 'status' => 'closed', 'show_results' => false]);
    $assessment->assignments()->create(['user_id' => $trainee->id, 'assigned_at' => now()]);
    $attempt = $assessment->attempts()->create([
        'user_id' => $trainee->id, 'attempt_number' => 1, 'status' => 'graded', 'started_at' => now()->subHour(),
        'submitted_at' => now(), 'earned_marks' => 9, 'total_marks' => 10, 'score_percentage' => 90, 'passed' => true,
    ]);

    $this->actingAs($trainee)->get(route('learning.assessments.index'))
        ->assertOk()->assertSee('Closed Confidential Test')->assertDontSee('90%');
    $this->actingAs($trainee)->get(route('learning.assessments.attempts.show', $attempt))
        ->assertOk()->assertSee('Results are not configured')->assertDontSee('90%');
});

test('expired attempts grade saved answers once and a passed test cannot be restarted', function (): void {
    $trainee = testFlowUser('trainee');
    $assessment = Assessment::factory()->published()->create(['max_attempts' => 2, 'passing_percentage' => 60]);
    $question = AssessmentQuestion::factory()->for($assessment)->create(['type' => 'single_choice', 'marks' => 1]);
    $correct = $question->options()->create(['option_text' => 'Correct', 'is_correct' => true, 'position' => 1]);
    $question->options()->create(['option_text' => 'Wrong', 'is_correct' => false, 'position' => 2]);
    $assessment->assignments()->create(['user_id' => $trainee->id, 'assigned_at' => now()]);
    $attempt = AssessmentAttempt::query()->create([
        'assessment_id' => $assessment->id, 'user_id' => $trainee->id, 'attempt_number' => 1,
        'status' => 'in_progress', 'started_at' => now()->subHour(), 'expires_at' => now()->subMinute(),
    ]);
    $attempt->answers()->create([
        'assessment_question_id' => $question->id, 'selected_option_ids' => [$correct->id], 'earned_marks' => 0, 'is_correct' => false,
    ]);

    $this->actingAs($trainee)->get(route('learning.assessments.attempts.show', $attempt))
        ->assertOk()->assertSee('Passed')->assertSee('100%');
    expect($attempt->fresh()->passed)->toBeTrue()
        ->and($attempt->fresh()->answers()->count())->toBe(1);

    $this->actingAs($trainee)->post(route('learning.assessments.start', $assessment))
        ->assertSessionHasErrors('attempt');
});
