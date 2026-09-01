<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->artisan('admin:permissions-sync')->assertSuccessful();
});

test('trainees receive the focused tab navigation without the sidebar', function (): void {
    $trainee = User::factory()->create();
    $trainee->syncRoles([Role::findByName('trainee')]);

    $response = $this->actingAs($trainee)->get(route('learning.dashboard'));

    $response->assertOk()
        ->assertDontSee('Toggle Sidebar')
        ->assertSee('Course')
        ->assertSee('My Courses')
        ->assertSee('Tests')
        ->assertSee('My Tests')
        ->assertSee('Feedback')
        ->assertSee('Coming soon')
        ->assertSee(route('learning.dashboard'), false)
        ->assertSee(route('learning.courses.index'), false)
        ->assertSee(route('learning.assessments.catalog'), false)
        ->assertSee(route('learning.assessments.index'), false)
        ->assertSee('aria-disabled="true"', false)
        ->assertSee('aria-current="page"', false);
});

test('staff portals continue to use the sidebar layout', function (): void {
    $admin = User::factory()->create();
    $admin->syncRoles([Role::findByName('admin')]);

    $this->actingAs($admin)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Toggle Sidebar');
});

test('trainee account pages keep the sidebar-free shell', function (): void {
    $trainee = User::factory()->create();
    $trainee->syncRoles([Role::findByName('trainee')]);

    foreach ([route('account.profile.edit'), route('account.password.edit')] as $url) {
        $this->actingAs($trainee)->get($url)
            ->assertOk()
            ->assertDontSee('Toggle Sidebar');
    }
});
