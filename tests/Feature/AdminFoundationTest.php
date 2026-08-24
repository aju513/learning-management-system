<?php

use App\Models\User;
use App\Services\NavigationService;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('public registration and email verification are disabled', function () {
    $this->get('/admin/register')->assertNotFound();
    $this->get('/admin/email/verify')->assertNotFound();
});

test('guests are redirected to the Fortify login', function () {
    $this->get('/portal')->assertRedirect('/admin/login');
    $this->get('/super-admin')->assertRedirect('/admin/login');
    $this->get('/admin')->assertRedirect('/admin/login');
    $this->get('/instructor')->assertRedirect('/admin/login');
    $this->get('/learning')->assertRedirect('/admin/login');
    $this->get('/admin/login')->assertOk()->assertSee('LMS sign in');
});

test('inactive users cannot sign in', function () {
    $user = User::factory()->create(['status' => 'inactive', 'password' => Hash::make('password')]);

    $this->post('/admin/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('the bootstrap account resolves to the Super Admin portal', function () {
    $this->artisan('admin:permissions-sync')->assertSuccessful();

    $this->post('/admin/login', ['email' => 'admin@admin.com', 'password' => 'admin'])
        ->assertRedirect(route('portal.home'));
    $this->get(route('portal.home'))->assertRedirect(route('super-admin.dashboard'));
    $this->get(route('super-admin.dashboard'))->assertOk()->assertSee('Super Admin Dashboard');
});

test('permission synchronization is exact and owns four fixed roles', function () {
    Permission::create(['name' => 'obsolete.permission', 'guard_name' => 'web']);

    $this->artisan('admin:permissions-sync')->assertSuccessful();

    expect(Permission::where('name', 'obsolete.permission')->exists())->toBeFalse()
        ->and(Permission::where('name', 'portals.trainee.access')->exists())->toBeTrue()
        ->and(Role::query()->orderBy('name')->pluck('name')->all())->toBe(['admin', 'instructor', 'super-admin', 'trainee'])
        ->and(User::find(1)->getRoleNames()->all())->toBe(['super-admin'])
        ->and(Role::findByName('super-admin')->permissions()->count())->toBe(PermissionCatalog::names()->count() - 3)
        ->and(Role::findByName('super-admin')->hasPermissionTo('portals.trainee.access'))->toBeFalse();
});

test('permission synchronization refuses assigned unsupported roles', function () {
    $this->artisan('admin:permissions-sync')->assertSuccessful();
    $custom = Role::create(['name' => 'operator', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($custom);

    $this->artisan('admin:permissions-sync')->assertFailed();

    expect($custom->fresh())->not->toBeNull()->and($user->fresh()->getRoleNames()->all())->toBe(['operator']);
});

test('menu regeneration validates and writes all portal manifests', function () {
    $this->artisan('admin:permissions-sync')->assertSuccessful();
    $this->artisan('admin:menu-regenerate')->assertSuccessful();

    $manifests = require base_path('bootstrap/cache/admin-menu.php');

    expect(array_keys($manifests))->toBe(['super-admin', 'admin', 'instructor', 'trainee']);
});

test('Admin creates fixed Instructor and Trainee accounts without role selection', function () {
    $this->artisan('admin:permissions-sync')->assertSuccessful();
    $admin = User::factory()->create();
    $admin->syncRoles(['admin']);

    $this->actingAs($admin)->post(route('admin.trainees.store'), [
        'name' => 'Example Trainee',
        'email' => 'trainee-account@example.com',
        'password' => 'SecurePassword1!',
        'password_confirmation' => 'SecurePassword1!',
        'status' => 'active',
        'roles' => ['admin'],
    ])->assertRedirect(route('admin.trainees.index'));

    $trainee = User::whereEmail('trainee-account@example.com')->firstOrFail();
    expect($trainee->getRoleNames()->all())->toBe(['trainee'])
        ->and(Activity::where('event', 'user.created')->where('causer_id', $admin->id)->exists())->toBeTrue();

    $this->actingAs($admin)->get('/super-admin/admins')->assertForbidden();
});

test('each role resolves to one dashboard and cannot enter another portal', function (string $role, string $dashboard, string $foreignPortal) {
    $this->artisan('admin:permissions-sync')->assertSuccessful();
    $user = User::factory()->create();
    $user->syncRoles([$role]);

    $this->actingAs($user)->get(route('portal.home'))->assertRedirect(route($dashboard));
    $this->actingAs($user)->get(route($dashboard))->assertOk();
    $this->actingAs($user)->get($foreignPortal)->assertForbidden();
})->with([
    'super admin' => ['super-admin', 'super-admin.dashboard', '/learning'],
    'admin' => ['admin', 'admin.dashboard', '/instructor'],
    'instructor' => ['instructor', 'instructor.dashboard', '/admin'],
    'trainee' => ['trainee', 'learning.dashboard', '/super-admin'],
]);

test('portal navigation is fixed per role instead of accumulating shared menu items', function () {
    $this->artisan('admin:permissions-sync')->assertSuccessful();
    $navigation = app(NavigationService::class);
    $users = collect(['super-admin', 'admin', 'instructor', 'trainee'])->mapWithKeys(function (string $role): array {
        $user = User::factory()->create();
        $user->syncRoles([$role]);

        return [$role => $user];
    });
    $superAdminNavigation = collect($navigation->forUser($users['super-admin']));
    $adminNavigation = collect($navigation->forUser($users['admin']));

    expect($superAdminNavigation->pluck('label')->all())->toBe(['Dashboard', 'User Management', 'Course Overview', 'Test Overview', 'System Settings', 'Reports', 'Activity Log'])
        ->and(collect($superAdminNavigation->firstWhere('label', 'Course Overview')['children'])->map(fn (array $item): array => ['key' => $item['key'], 'label' => $item['label']])->all())->toBe([
            ['key' => 'courses', 'label' => 'Courses'],
            ['key' => 'applications', 'label' => 'Applications'],
            ['key' => 'enrollments', 'label' => 'Enrollments'],
        ])
        ->and(collect($superAdminNavigation->firstWhere('label', 'Test Overview')['children'])->map(fn (array $item): array => ['key' => $item['key'], 'label' => $item['label']])->all())->toBe([
            ['key' => 'tests', 'label' => 'Quizzes'],
            ['key' => 'results', 'label' => 'Results'],
        ])
        ->and(collect($superAdminNavigation->firstWhere('label', 'System Settings')['children'])->map(fn (array $item): array => ['key' => $item['key'], 'label' => $item['label']])->all())->toBe([
            ['key' => 'fiscal-years', 'label' => 'Fiscal Years'],
            ['key' => 'categories', 'label' => 'Categories'],
            ['key' => 'credit-scores', 'label' => 'Credit Score Viewer'],
        ])
        ->and($superAdminNavigation->flatMap(fn (array $item): array => $item['children'] ?? [])->pluck('label')->all())->not->toContain('Access Matrix')
        ->and($adminNavigation->pluck('label')->all())->toBe(['Dashboard', 'People', 'Course Overview', 'Test Overview', 'System Settings', 'Reports'])
        ->and($adminNavigation->firstWhere('label', 'Course Overview')['children'])->toHaveCount(4)
        ->and($adminNavigation->firstWhere('label', 'Test Overview')['children'])->toHaveCount(4)
        ->and(collect($adminNavigation->firstWhere('label', 'System Settings')['children'])->pluck('label')->all())->toBe(['Categories', 'Fiscal Years', 'Credit Score Viewer'])
        ->and($adminNavigation->pluck('label')->all())->not->toContain('My Learning', 'My Courses')
        ->and(collect($navigation->forUser($users['instructor']))->pluck('label')->all())->toContain('My Courses', 'My Trainees')
        ->and(collect($navigation->forUser($users['trainee']))->pluck('label')->all())->toBe(['Overview', 'Courses', 'Tests', 'Credit Scores'])
        ->and(collect($navigation->forUser($users['trainee']))->pluck('label')->all())->not->toContain('Enrollments', 'User Management');
});

test('the Super Admin oversight screens render without learner features', function () {
    $this->artisan('admin:permissions-sync')->assertSuccessful();
    $superAdmin = User::findOrFail(1);

    foreach ([
        route('super-admin.dashboard'), route('super-admin.admins.index'), route('super-admin.instructors.index'),
        route('super-admin.trainees.index'), route('super-admin.courses.index'), route('super-admin.course-categories.index'),
        route('super-admin.applications.index'), route('super-admin.enrollments.index'), route('super-admin.assessments.index'),
        route('super-admin.results.index'), route('super-admin.reports.index'), route('super-admin.access-matrix.index'),
        route('super-admin.activity.index'), route('account.profile.edit'), route('account.password.edit'),
    ] as $url) {
        $this->actingAs($superAdmin)->get($url)->assertOk();
    }

    $this->actingAs($superAdmin)->get(route('learning.catalog.index'))->assertForbidden();
    expect(Route::has('admin.roles.create'))->toBeFalse();
});

test('Admins can access course and test reports plus system settings', function () {
    $this->artisan('admin:permissions-sync')->assertSuccessful();
    $admin = User::factory()->create();
    $admin->syncRoles(['admin']);

    foreach ([
        route('admin.courses.index'), route('admin.enrollments.index'), route('admin.assessments.index'),
        route('admin.course-reports.index'), route('admin.test-reports.index'), route('admin.course-categories.index'),
        route('admin.fiscal-years.index'),
    ] as $url) {
        $this->actingAs($admin)->get($url)->assertOk();
    }
});
