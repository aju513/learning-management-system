<?php

use App\Models\Assessment;
use App\Models\AssessmentCategory;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->artisan('admin:permissions-sync')->assertSuccessful();
});

function catalogTrainee(): User
{
    $trainee = User::factory()->create();
    $trainee->syncRoles([Role::findByName('trainee')]);

    return $trainee;
}

function catalogAdmin(): User
{
    $admin = User::factory()->create();
    $admin->syncRoles([Role::findByName('admin')]);

    return $admin;
}

test('trainees can browse published tests and their categories without an assignment', function (): void {
    $trainee = catalogTrainee();
    $category = AssessmentCategory::factory()->create(['name' => 'Leadership Tests']);
    Assessment::factory()->published()->create(['category_id' => $category->id, 'title' => 'Leadership Fundamentals']);
    Assessment::factory()->create(['title' => 'Draft Test']);

    $response = $this->actingAs($trainee)->get(route('learning.assessments.catalog'));

    $response->assertOk()
        ->assertSee('Test Catalog')
        ->assertSee('Leadership Fundamentals')
        ->assertSee('Explore test categories')
        ->assertSee('Leadership Tests')
        ->assertDontSee('Draft Test');
});

test('test catalog filters by category and protects taking until assignment', function (): void {
    $trainee = catalogTrainee();
    $leadership = AssessmentCategory::factory()->create(['name' => 'Leadership']);
    $finance = AssessmentCategory::factory()->create(['name' => 'Finance']);
    $test = Assessment::factory()->published()->create(['category_id' => $leadership->id, 'title' => 'Leadership Test']);
    Assessment::factory()->published()->create(['category_id' => $finance->id, 'title' => 'Finance Test']);

    $this->actingAs($trainee)->get(route('learning.assessments.catalog', ['category_id' => $leadership->id]))
        ->assertOk()->assertSee('Leadership Test')->assertDontSee('Finance Test');

    $this->actingAs($trainee)->get(route('learning.assessments.catalog.show', $test))
        ->assertOk()->assertSee('Before you start')->assertSee('Leadership Test');

    $this->actingAs($trainee)->post(route('learning.assessments.start', $test))
        ->assertForbidden();
});

test('test catalog excludes unavailable training-restricted tests', function (): void {
    $trainee = catalogTrainee();
    Assessment::factory()->published()->create(['title' => 'Restricted Test', 'availability_scope' => 'training', 'required_training_key' => 'missing-training']);

    $this->actingAs($trainee)->get(route('learning.assessments.catalog'))
        ->assertOk()->assertDontSee('Restricted Test');
});

test('admins can manage test categories and cannot delete a category in use', function (): void {
    $admin = catalogAdmin();

    $this->actingAs($admin)->post(route('admin.assessment-categories.store'), [
        'name' => 'Compliance Tests', 'description' => 'Required compliance knowledge.', 'is_active' => 1,
    ])->assertRedirect(route('admin.assessment-categories.index'));

    $category = AssessmentCategory::query()->where('name', 'Compliance Tests')->firstOrFail();
    $this->actingAs($admin)->get(route('admin.assessment-categories.index'))
        ->assertOk()->assertSee('Compliance Tests');

    Assessment::factory()->create(['category_id' => $category->id]);
    $this->actingAs($admin)->delete(route('admin.assessment-categories.destroy', $category))
        ->assertSessionHasErrors('category');
});
