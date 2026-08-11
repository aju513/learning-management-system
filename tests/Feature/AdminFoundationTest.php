<?php

use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('public registration and email verification are disabled', function () {
    $this->get('/admin/register')->assertNotFound();
    $this->get('/admin/email/verify')->assertNotFound();
});

test('guests are redirected to the prefixed admin login', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
    $this->get('/admin/login')->assertOk()->assertSee('Admin sign in');
});

test('inactive users cannot sign in', function () {
    $user = User::factory()->create(['status' => 'inactive', 'password' => Hash::make('password')]);

    $this->post('/admin/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('the bootstrap account can access the admin without a forced password change', function () {
    $this->artisan('admin:permissions-sync')->assertSuccessful();

    $this->post('/admin/login', ['email' => 'admin@admin.com', 'password' => 'admin'])
        ->assertRedirect('/admin');
    $this->get('/admin')->assertOk();
});

test('permission synchronization is exact and assigns super admin', function () {
    Permission::create(['name' => 'obsolete.permission', 'guard_name' => 'web']);

    $this->artisan('admin:permissions-sync')->assertSuccessful();

    expect(Permission::where('name', 'obsolete.permission')->exists())->toBeFalse()
        ->and(Permission::where('name', 'dashboard.view')->exists())->toBeTrue()
        ->and(Permission::findByName('users.manage')->view_title)->toBe('Manage users')
        ->and(Permission::findByName('users.manage')->description)->toBe('Allows access to the users index, filters, and pagination.')
        ->and(User::find(1)->hasRole('super-admin'))->toBeTrue()
        ->and(Role::findByName('super-admin')->permissions()->count())->toBe(PermissionCatalog::names()->count());
});

test('menu regeneration validates and writes the manifest', function () {
    $this->artisan('admin:menu-regenerate')->assertSuccessful();
    expect(file_exists(base_path('bootstrap/cache/admin-menu.php')))->toBeTrue();
});

test('authorized administrators can create a simple user account', function () {
    $this->artisan('admin:permissions-sync')->assertSuccessful();
    $admin = User::findOrFail(1);
    $role = Role::create(['name' => 'operator', 'guard_name' => 'web']);

    $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Example Operator',
        'email' => 'operator@example.com',
        'password' => 'SecurePassword1!',
        'password_confirmation' => 'SecurePassword1!',
        'status' => 'active',
        'roles' => [$role->name],
    ])->assertRedirect(route('admin.users.index'));

    $user = User::whereEmail('operator@example.com')->firstOrFail();
    expect($user->hasRole('operator'))->toBeTrue();
});

test('the final super administrator cannot be deleted', function () {
    $this->artisan('admin:permissions-sync')->assertSuccessful();
    $admin = User::findOrFail(1);

    $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))
        ->assertSessionHasErrors('user');
    expect($admin->fresh())->not->toBeNull();
});

test('the super administrator can render every foundation screen', function () {
    $this->artisan('admin:permissions-sync')->assertSuccessful();
    $admin = User::findOrFail(1);

    foreach ([
        route('admin.dashboard'),
        route('admin.users.index'),
        route('admin.users.show', $admin),
        route('admin.users.create'),
        route('admin.users.edit', $admin),
        route('admin.roles.index'),
        route('admin.roles.show', Role::findByName('super-admin')),
        route('admin.roles.create'),
        route('admin.roles.edit', Role::findByName('super-admin')),
        route('admin.permissions.index'),
        route('admin.activity.index'),
        route('admin.profile.edit'),
        route('admin.password.edit'),
        route('admin.ui-kit'),
    ] as $url) {
        $this->actingAs($admin)->get($url)->assertOk();
    }

    $this->actingAs($admin)->get(route('admin.permissions.index'))
        ->assertSee('Manage users')
        ->assertSee('Allows access to the users index, filters, and pagination.');
});

test('crud permissions protect matching user and role actions', function () {
    $this->artisan('admin:permissions-sync')->assertSuccessful();
    $operator = User::factory()->create();
    $role = Role::create(['name' => 'operator', 'guard_name' => 'web']);
    $role->givePermissionTo(['users.manage', 'users.show', 'users.create', 'roles.manage']);
    $operator->assignRole($role);
    $target = User::factory()->create();

    $this->actingAs($operator)->get(route('admin.users.index'))->assertOk();
    $this->actingAs($operator)->get(route('admin.users.show', $target))->assertOk();
    $this->actingAs($operator)->get(route('admin.users.create'))->assertOk();
    $this->actingAs($operator)->post(route('admin.users.store'), [
        'name' => 'Unassigned User',
        'email' => 'unassigned@example.com',
        'password' => 'SecurePassword1!',
        'password_confirmation' => 'SecurePassword1!',
        'status' => 'active',
    ])->assertRedirect(route('admin.users.index'));
    $this->actingAs($operator)->get(route('admin.users.edit', $target))->assertForbidden();
    $this->actingAs($operator)->get(route('admin.roles.index'))->assertOk();
    $this->actingAs($operator)->get(route('admin.roles.show', $role))->assertForbidden();
    $this->actingAs($operator)->delete(route('admin.users.destroy', $target))->assertForbidden();
});

test('user and role mutations record the acting administrator', function () {
    $this->artisan('admin:permissions-sync')->assertSuccessful();
    $admin = User::findOrFail(1);
    $role = Role::create(['name' => 'operator', 'guard_name' => 'web']);

    $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Activity User',
        'email' => 'activity-user@example.com',
        'password' => 'SecurePassword1!',
        'password_confirmation' => 'SecurePassword1!',
        'status' => 'active',
        'roles' => [$role->name],
    ])->assertRedirect(route('admin.users.index'));

    $this->actingAs($admin)->post(route('admin.roles.store'), [
        'name' => 'auditor',
        'permissions' => ['users.manage'],
    ])->assertRedirect(route('admin.roles.index'));

    expect(Activity::where('event', 'user.created')->where('causer_id', $admin->id)->exists())->toBeTrue()
        ->and(Activity::where('event', 'role.created')->where('causer_id', $admin->id)->exists())->toBeTrue();
});

test('authorized administrators can bulk update and delete users', function () {
    $this->artisan('admin:permissions-sync')->assertSuccessful();
    $admin = User::findOrFail(1);
    $users = User::factory()->count(2)->create();

    $this->actingAs($admin)->patch(route('admin.users.bulk-status'), [
        'users' => $users->pluck('id')->all(),
        'status' => 'inactive',
    ])->assertRedirect();

    expect($users->fresh()->every(fn (User $user): bool => ! $user->isActive()))->toBeTrue();

    $this->actingAs($admin)->delete(route('admin.users.bulk-destroy'), [
        'users' => $users->pluck('id')->all(),
    ])->assertRedirect();

    expect(User::whereKey($users->pluck('id'))->count())->toBe(0);
});
