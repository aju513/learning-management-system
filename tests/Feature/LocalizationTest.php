<?php

test('the interface can switch between English and Nepali labels', function () {
    $this->get(route('login'))->assertSee('LMS sign in');

    $this->post(route('locale.update'), ['locale' => 'ne'])->assertRedirect();

    $this->get(route('login'))
        ->assertSee('LMS साइन इन')
        ->assertSee('नेपाली');

    $this->post(route('locale.update'), ['locale' => 'en'])->assertRedirect();
    $this->get(route('login'))->assertSee('LMS sign in');
});

test('unsupported interface locales are rejected', function () {
    $this->post(route('locale.update'), ['locale' => 'fr'])
        ->assertSessionHasErrors('locale');
});

test('Nepali labels are applied to the authenticated portal navigation', function () {
    $this->artisan('admin:permissions-sync')->assertSuccessful();
    $admin = \App\Models\User::factory()->create();
    $admin->syncRoles(['admin']);

    $this->withSession(['locale' => 'ne'])
        ->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('पाठ्यक्रम अवलोकन')
        ->assertSee('प्रणाली सेटिङहरू');
});
