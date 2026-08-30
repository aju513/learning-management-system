<?php

use App\Models\Assessment;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->artisan('admin:permissions-sync')->assertSuccessful();
});

function assessmentTrainee(): User
{
    $trainee = User::factory()->create();
    $trainee->syncRoles([Role::findByName('trainee')]);

    return $trainee;
}

function assignAssessment(Assessment $assessment, User $trainee, ?string $dueAt = null): void
{
    $assessment->assignments()->create([
        'user_id' => $trainee->id,
        'assigned_by' => $assessment->created_by,
        'assigned_at' => now(),
        'due_at' => $dueAt,
    ]);
}

test('trainee assessment cards show assignment states and actions', function () {
    $trainee = assessmentTrainee();
    $instructor = User::factory()->create();

    $pending = Assessment::factory()->published()->for($instructor, 'creator')->create(['title' => 'Pending Assignment']);
    assignAssessment($pending, $trainee, now()->addDays(3));

    $completed = Assessment::factory()->published()->for($instructor, 'creator')->create(['title' => 'Completed Assessment']);
    assignAssessment($completed, $trainee);
    $completed->attempts()->create([
        'user_id' => $trainee->id, 'attempt_number' => 1, 'status' => 'graded',
        'started_at' => now()->subDays(2), 'submitted_at' => now()->subDay(), 'total_marks' => 10, 'score_percentage' => 86, 'passed' => true,
    ]);

    $failed = Assessment::factory()->published()->for($instructor, 'creator')->create(['title' => 'Retry Assessment', 'max_attempts' => 2]);
    assignAssessment($failed, $trainee);
    $failed->attempts()->create([
        'user_id' => $trainee->id, 'attempt_number' => 1, 'status' => 'graded',
        'started_at' => now()->subDays(3), 'submitted_at' => now()->subDays(2), 'total_marks' => 10, 'score_percentage' => 48, 'passed' => false,
    ]);

    $response = $this->actingAs($trainee)->get(route('learning.assessments.index'));

    $response->assertOk()
        ->assertSee('Tests &amp; Assessments', false)
        ->assertSee('Pending Assignment')
        ->assertSee('Pending')
        ->assertSee('Completed Assessment')
        ->assertSee('86%')
        ->assertSee('View Result')
        ->assertSee('Retry Assessment')
        ->assertSee('Retry Test')
        ->assertSee('Start Test');
});

test('trainee can filter assessments by search and attempt status', function () {
    $trainee = assessmentTrainee();
    $instructor = User::factory()->create();

    $completed = Assessment::factory()->published()->for($instructor, 'creator')->create(['title' => 'Completed Ethics Test']);
    assignAssessment($completed, $trainee);
    $completed->attempts()->create([
        'user_id' => $trainee->id, 'attempt_number' => 1, 'status' => 'graded',
        'started_at' => now()->subHour(), 'submitted_at' => now(), 'total_marks' => 10, 'score_percentage' => 90, 'passed' => true,
    ]);

    $pending = Assessment::factory()->published()->for($instructor, 'creator')->create(['title' => 'Pending Governance Test']);
    assignAssessment($pending, $trainee, now()->addDay());

    $this->actingAs($trainee)->get(route('learning.assessments.index', ['status' => 'completed']))
        ->assertOk()->assertSee('Completed Ethics Test')->assertDontSee('Pending Governance Test');

    $this->actingAs($trainee)->get(route('learning.assessments.index', ['search' => 'Governance']))
        ->assertOk()->assertSee('Pending Governance Test')->assertDontSee('Completed Ethics Test');
});

test('assessment list filters reject unsupported values', function () {
    $this->actingAs(assessmentTrainee())
        ->get(route('learning.assessments.index', ['status' => 'unknown']))
        ->assertSessionHasErrors('status');
});
