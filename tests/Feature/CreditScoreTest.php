<?php

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\CreditAward;
use App\Models\Enrollment;
use App\Models\FiscalYear;
use App\Models\User;
use App\Services\CreditScoreService;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->artisan('admin:permissions-sync')->assertSuccessful();
});

function creditPortalUser(string $role): User
{
    $user = User::factory()->create();
    $user->syncRoles([Role::findByName($role)]);

    return $user;
}

test('Super Admin can create and activate a fiscal year', function () {
    $admin = creditPortalUser('super-admin');

    $this->actingAs($admin)->post(route('super-admin.fiscal-years.store'), [
        'name' => 'FY 2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31',
        'attendance_threshold_days' => 90, 'attendance_credit_points' => 10,
    ])->assertRedirect();

    $fiscalYear = FiscalYear::firstOrFail();
    expect($fiscalYear->status->value)->toBe('draft');

    $this->actingAs($admin)->patch(route('super-admin.fiscal-years.status', $fiscalYear), ['status' => 'active'])
        ->assertRedirect();
    expect($fiscalYear->refresh()->status->value)->toBe('active');
});

test('trainees can refresh attendance and claim the annual attendance award', function () {
    $trainee = creditPortalUser('trainee');
    $fiscalYear = FiscalYear::factory()->create([
        'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'attendance_threshold_days' => 90,
        'attendance_credit_points' => 10, 'status' => 'active',
    ]);
    config(['services.tmis.attendance.sandbox_days' => 90]);

    $this->actingAs($trainee)->get(route('learning.credit-scores.index'))->assertOk()->assertSee('0 / 90');
    $this->actingAs($trainee)->post(route('learning.credit-scores.attendance.refresh'))->assertRedirect();
    $this->assertDatabaseHas('attendance_snapshots', ['fiscal_year_id' => $fiscalYear->id, 'user_id' => $trainee->id, 'present_days' => 90]);

    $award = CreditAward::firstOrFail();
    $this->actingAs($trainee)->post(route('learning.credit-scores.claim', $award))->assertRedirect();
    expect($award->refresh()->status->value)->toBe('claimed');
});

test('trainee dashboard renders the credit summary header', function () {
    $trainee = creditPortalUser('trainee');
    FiscalYear::factory()->create([
        'name' => 'FY 2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'status' => 'active',
    ]);

    $this->actingAs($trainee)->get(route('learning.dashboard'))
        ->assertOk()
        ->assertSee('FY 2026')
        ->assertSee('0.00 credits');
});

test('trainee navbar reflects eligible and claimed credit scores', function () {
    $trainee = creditPortalUser('trainee');
    $fiscalYear = FiscalYear::factory()->create([
        'name' => 'FY 2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'status' => 'active',
    ]);
    $award = CreditAward::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'user_id' => $trainee->id,
        'points' => 5,
        'status' => 'eligible',
    ]);

    $this->actingAs($trainee)->get(route('learning.dashboard'))
        ->assertOk()
        ->assertSee('+5.00 ready');

    $this->actingAs($trainee)->post(route('learning.credit-scores.claim', $award))->assertRedirect();

    $this->actingAs($trainee)->get(route('learning.dashboard'))
        ->assertOk()
        ->assertSee('5.00 credits')
        ->assertDontSee('+5.00 ready');
});

test('trainee credit summary is available on the enrolled courses page', function () {
    $trainee = creditPortalUser('trainee');
    FiscalYear::factory()->create([
        'name' => 'FY 2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'status' => 'active',
    ]);

    $this->actingAs($trainee)->get(route('learning.courses.index'))
        ->assertOk()
        ->assertSee(route('learning.credit-scores.index'), false)
        ->assertSee('FY 2026')
        ->assertSee('0.00 credits');
});

test('fiscal year management links include their fiscal year parameter', function () {
    $superAdmin = creditPortalUser('super-admin');
    $admin = creditPortalUser('admin');
    $fiscalYear = FiscalYear::factory()->create(['status' => 'draft']);

    $this->actingAs($superAdmin)->get(route('super-admin.fiscal-years.index'))
        ->assertOk()
        ->assertSee(route('super-admin.fiscal-years.show', $fiscalYear), false)
        ->assertSee(route('super-admin.fiscal-years.edit', $fiscalYear), false);

    $this->actingAs($admin)->get(route('admin.fiscal-years.index'))
        ->assertOk()
        ->assertSee(route('admin.fiscal-years.show', $fiscalYear), false)
        ->assertSee(route('admin.fiscal-years.edit', $fiscalYear), false);
});

test('course completion credit is created only once for a learner and fiscal year', function () {
    $trainee = creditPortalUser('trainee');
    $course = Course::factory()->create(['credit_points' => 5]);
    FiscalYear::factory()->create(['starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'status' => 'active']);

    $service = app(CreditScoreService::class);
    $first = $service->recordCourseCompletion($course, $trainee, now());
    $second = $service->recordCourseCompletion($course, $trainee, now());

    expect($first->id)->toBe($second->id)->and(CreditAward::count())->toBe(1);
});

test('credit scores page shows course credit availability and taken state', function () {
    $trainee = creditPortalUser('trainee');
    $fiscalYear = FiscalYear::factory()->create(['starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'status' => 'active']);
    $course = Course::factory()->published()->create(['title' => 'Leadership Essentials', 'credit_points' => 5]);

    $this->actingAs($trainee)->get(route('learning.credit-scores.index'))
        ->assertOk()
        ->assertSee('Leadership Essentials')
        ->assertSee('Available')
        ->assertSee('+5.00');

    Enrollment::factory()->create(['course_id' => $course->id, 'user_id' => $trainee->id, 'status' => 'active']);

    $this->actingAs($trainee)->get(route('learning.credit-scores.index'))
        ->assertOk()
        ->assertSee('Enrolled');

    $award = CreditAward::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'user_id' => $trainee->id,
        'course_id' => $course->id,
        'source_key' => 'course:'.$course->id,
    ]);

    $this->actingAs($trainee)->get(route('learning.credit-scores.index'))
        ->assertOk()
        ->assertSee('Credit score ready');

    $award->update(['status' => 'claimed']);

    $this->actingAs($trainee)->get(route('learning.credit-scores.index'))
        ->assertOk()
        ->assertSee('Credit score taken');
});

test('enrolled course cards show assigned credit scores and claim state', function () {
    $trainee = creditPortalUser('trainee');
    $course = Course::factory()->published()->create(['title' => 'Credit Bearing Course', 'credit_points' => 7.5]);
    Enrollment::factory()->create(['course_id' => $course->id, 'user_id' => $trainee->id, 'status' => 'active']);

    $this->actingAs($trainee)->get(route('learning.courses.index'))
        ->assertOk()
        ->assertSee('+7.50')
        ->assertSee('Earn after course completion');

    $award = CreditAward::factory()->create([
        'fiscal_year_id' => FiscalYear::factory()->create()->id,
        'user_id' => $trainee->id,
        'course_id' => $course->id,
        'source_key' => 'course:'.$course->id,
        'points' => 7.5,
        'status' => 'eligible',
    ]);

    $this->actingAs($trainee)->get(route('learning.courses.index'))
        ->assertOk()
        ->assertSee('Claim credit')
        ->assertSee(route('learning.credit-scores.claim', $award), false);

    $award->update(['status' => 'claimed']);
    $this->actingAs($trainee)->get(route('learning.courses.index'))
        ->assertOk()
        ->assertSee('Credit claimed');
});

test('previously completed credit-bearing enrollments can recover and claim missing awards', function () {
    $trainee = creditPortalUser('trainee');
    FiscalYear::factory()->create(['starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'status' => 'active']);
    $course = Course::factory()->published()->create(['title' => 'Earlier Completed Course', 'credit_points' => 4]);
    $enrollment = Enrollment::factory()->create([
        'course_id' => $course->id,
        'user_id' => $trainee->id,
        'status' => 'completed',
        'progress_percentage' => 100,
        'completed_at' => now()->subMonth(),
    ]);

    $this->actingAs($trainee)->get(route('learning.courses.index'))
        ->assertOk()
        ->assertSee('+4.00')
        ->assertSee('Claim credit')
        ->assertSee(route('learning.credit-scores.course-claim', $enrollment), false);

    $this->actingAs($trainee)->post(route('learning.credit-scores.course-claim', $enrollment))->assertRedirect();
    $this->assertDatabaseHas('credit_awards', [
        'user_id' => $trainee->id,
        'course_id' => $course->id,
        'source_key' => 'course:'.$course->id,
        'points' => 4,
        'status' => 'claimed',
    ]);
});

test('completed catalog course details expose an existing eligible credit claim', function () {
    $trainee = creditPortalUser('trainee');
    $instructor = creditPortalUser('instructor');
    $fiscalYear = FiscalYear::factory()->create(['starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'status' => 'active']);
    $course = Course::factory()->published()->for($instructor, 'instructor')->create([
        'title' => 'Completed Governance Course',
        'credit_points' => 5,
    ]);
    $enrollment = Enrollment::factory()->for($course)->for($trainee, 'trainee')->create([
        'status' => 'completed',
        'progress_percentage' => 100,
        'completed_at' => now()->subDay(),
    ]);
    $award = CreditAward::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'user_id' => $trainee->id,
        'course_id' => $course->id,
        'source_key' => 'course:'.$course->id,
        'points' => 5,
        'status' => 'eligible',
    ]);

    $this->actingAs($trainee)->get(route('learning.catalog.show', $course))
        ->assertOk()
        ->assertSee('Claim 5.00 credits')
        ->assertSee(route('learning.credit-scores.claim', $award), false);
});

test('credit scores page shows detailed test credit opportunities', function () {
    $trainee = creditPortalUser('trainee');
    $instructor = creditPortalUser('instructor');
    FiscalYear::factory()->create(['starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'status' => 'active']);
    $assessment = Assessment::factory()->published()->for($instructor, 'creator')->create([
        'title' => 'Leadership Assessment',
        'credit_points' => 3,
        'duration_minutes' => 45,
        'passing_percentage' => 70,
    ]);
    $assessment->assignments()->create(['user_id' => $trainee->id, 'assigned_by' => $instructor->id, 'assigned_at' => now()]);

    $this->actingAs($trainee)->get(route('learning.credit-scores.index'))
        ->assertOk()
        ->assertSee('Test credit scores')
        ->assertSee('Test module')
        ->assertSee('Leadership Assessment')
        ->assertSee('+3.00')
        ->assertSee('45 minutes')
        ->assertSee('Pass 70%');
});

test('a trainee cannot claim another trainee credit award', function () {
    $trainee = creditPortalUser('trainee');
    $other = creditPortalUser('trainee');
    $fiscalYear = FiscalYear::factory()->create(['starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'status' => 'active']);
    $award = CreditAward::factory()->create(['fiscal_year_id' => $fiscalYear->id, 'user_id' => $other->id]);

    $this->actingAs($trainee)->post(route('learning.credit-scores.claim', $award))->assertForbidden();
});

test('admins and super admins can review a trainee fiscal-year credit breakdown', function () {
    $admin = creditPortalUser('admin');
    $superAdmin = creditPortalUser('super-admin');
    $trainee = creditPortalUser('trainee');
    $fiscalYear = FiscalYear::factory()->create([
        'name' => 'FY 2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'status' => 'active',
    ]);
    $course = Course::factory()->published()->create(['title' => 'Leadership Essentials']);
    Enrollment::factory()->create([
        'course_id' => $course->id, 'user_id' => $trainee->id, 'status' => 'completed',
        'enrolled_at' => '2026-02-01', 'completed_at' => '2026-03-01',
    ]);
    CreditAward::factory()->create([
        'fiscal_year_id' => $fiscalYear->id, 'user_id' => $trainee->id, 'course_id' => $course->id,
        'source_type' => 'course_completion', 'source_key' => 'course:'.$course->id,
        'source_label' => $course->title, 'points' => 5, 'status' => 'claimed', 'claimed_at' => '2026-03-01',
    ]);
    $assessment = Assessment::factory()->published()->create(['title' => 'Leadership Quiz', 'credit_points' => 3]);
    AssessmentAttempt::query()->create([
        'assessment_id' => $assessment->id, 'user_id' => $trainee->id, 'attempt_number' => 1,
        'status' => 'graded', 'started_at' => '2026-04-01 09:00:00', 'submitted_at' => '2026-04-01 09:20:00',
        'earned_marks' => 8, 'total_marks' => 10, 'score_percentage' => 80, 'passed' => true,
    ]);
    CreditAward::factory()->create([
        'fiscal_year_id' => $fiscalYear->id, 'user_id' => $trainee->id, 'assessment_id' => $assessment->id,
        'source_type' => 'assessment_pass', 'source_key' => 'assessment:'.$assessment->id,
        'source_label' => $assessment->title, 'points' => 3, 'status' => 'eligible',
    ]);

    $this->actingAs($admin)->get(route('admin.credit-scores.index', [
        'fiscal_year_id' => $fiscalYear->id, 'trainee_id' => $trainee->id, 'tab' => 'courses',
    ]))->assertOk()->assertSee('Leadership Essentials')->assertSee('5.00');

    $this->actingAs($superAdmin)->get(route('super-admin.credit-scores.index', [
        'fiscal_year_id' => $fiscalYear->id, 'trainee_id' => $trainee->id, 'tab' => 'quizzes',
    ]))->assertOk()->assertSee('Leadership Quiz')->assertSee('Passed')->assertSee('3.00');

    $this->actingAs($admin)->get(route('admin.credit-scores.index', [
        'fiscal_year_id' => $fiscalYear->id, 'trainee_id' => $trainee->id, 'tab' => 'overall',
    ]))->assertOk()->assertSee('Overall')->assertSee('8.00')->assertSee('Claimed');
});

test('credit score viewer is restricted to staff with the all-trainee permission', function () {
    $trainee = creditPortalUser('trainee');
    $fiscalYear = FiscalYear::factory()->create();

    $this->actingAs($trainee)->get(route('admin.credit-scores.index', ['fiscal_year_id' => $fiscalYear->id]))->assertForbidden();
});
