<?php

use App\Models\Assessment;
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
