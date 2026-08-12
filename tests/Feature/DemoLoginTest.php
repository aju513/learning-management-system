<?php

use App\Models\User;
use Database\Seeders\LmsDemoSeeder;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    config()->set('lms.demo_login.enabled', true);
    $this->artisan('admin:permissions-sync')->assertSuccessful();
    $this->seed(LmsDemoSeeder::class);
});

test('the login screen displays all configured demo account buttons', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('Quick demo access')
        ->assertSee('Super Admin')
        ->assertSee('Admin')
        ->assertSee('Instructor')
        ->assertSee('Trainee');
});

test('a guest can authenticate as each seeded demo account', function (string $account, string $email, string $dashboard) {
    $response = $this->post(route('admin.demo-login'), ['account' => $account]);

    $user = User::query()->where('email', $email)->firstOrFail();

    $response->assertRedirect(route('portal.home'));
    $this->assertAuthenticatedAs($user);
    $this->get(route('portal.home'))->assertRedirect(route($dashboard));
    expect(Activity::query()
        ->where('event', 'auth.demo-login')
        ->where('causer_id', $user->id)
        ->exists())->toBeTrue();
})->with([
    'super administrator' => ['super-admin', 'superadmin@example.com', 'super-admin.dashboard'],
    'administrator' => ['admin', 'lms.admin@example.com', 'admin.dashboard'],
    'instructor' => ['instructor', 'instructor@example.com', 'instructor.dashboard'],
    'trainee' => ['trainee', 'trainee@example.com', 'learning.dashboard'],
]);

test('an unknown demo account is rejected', function () {
    $this->post(route('admin.demo-login'), ['account' => 'unknown'])
        ->assertSessionHasErrors('account');

    $this->assertGuest();
});

test('an inactive seeded demo account cannot authenticate', function () {
    User::query()->where('email', 'trainee@example.com')->update(['status' => 'inactive']);

    $this->post(route('admin.demo-login'), ['account' => 'trainee'])
        ->assertSessionHasErrors('account');

    $this->assertGuest();
});

test('demo login buttons and endpoint are disabled by configuration', function () {
    config()->set('lms.demo_login.enabled', false);

    $this->get('/admin/login')
        ->assertOk()
        ->assertDontSee('Quick demo access');

    $this->post(route('admin.demo-login'), ['account' => 'trainee'])
        ->assertForbidden();

    $this->assertGuest();
});
