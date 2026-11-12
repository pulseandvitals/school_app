<?php

use App\Enums\HabitatType;
use App\Models\GrowthRule;
use App\Models\Habitat;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->teacher = User::factory()->create();
});

test('guests are redirected to login', function () {
    $this->get(route('aquariums.index'))->assertRedirect(route('login'));
});

test('a teacher creates an aquarium', function () {
    $response = $this->actingAs($this->teacher)->post(route('aquariums.store'), [
        'name' => 'Grade 3 - Section A',
        'description' => 'Morning class',
        'theme' => 'deep-ocean',
    ]);

    $habitat = Habitat::sole();
    $response->assertRedirect(route('aquariums.show', $habitat));
    expect($habitat->type)->toBe(HabitatType::Aquarium)
        ->and($habitat->user_id)->toBe($this->teacher->id)
        ->and($habitat->theme)->toBe('deep-ocean');
});

test('a theme from the other module is rejected', function () {
    $this->actingAs($this->teacher)
        ->post(route('aquariums.store'), ['name' => 'Class', 'theme' => 'sunny-meadow'])
        ->assertSessionHasErrors('theme');
});

test('another teacher\'s growth rule cannot be attached', function () {
    $foreignRule = GrowthRule::factory()->create();

    $this->actingAs($this->teacher)
        ->post(route('aquariums.store'), ['name' => 'Class', 'theme' => 'coral-reef', 'growth_rule_id' => $foreignRule->id])
        ->assertSessionHasErrors('growth_rule_id');
});

test('the index only lists the teacher\'s own habitats of that type', function () {
    Habitat::factory()->for($this->teacher)->create(['name' => 'Mine']);
    Habitat::factory()->for($this->teacher)->garden()->create();
    Habitat::factory()->archived()->for($this->teacher)->create();
    Habitat::factory()->create();

    $this->actingAs($this->teacher)
        ->get(route('aquariums.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Habitats/Index')
            ->has('habitats', 1)
            ->where('habitats.0.name', 'Mine')
            ->has('archived', 1));
});

test('another teacher\'s aquarium is not found', function () {
    $foreign = Habitat::factory()->create();

    $this->actingAs($this->teacher)->get(route('aquariums.show', $foreign))->assertNotFound();
    $this->actingAs($this->teacher)->put(route('aquariums.update', $foreign), ['name' => 'x', 'theme' => 'coral-reef'])->assertNotFound();
    $this->actingAs($this->teacher)->delete(route('aquariums.destroy', $foreign))->assertNotFound();
    expect($foreign->fresh()->trashed())->toBeFalse();
});

test('a garden cannot be opened through the aquarium routes', function () {
    $garden = Habitat::factory()->for($this->teacher)->garden()->create();

    $this->actingAs($this->teacher)->get(route('aquariums.show', $garden))->assertNotFound();
    $this->actingAs($this->teacher)->get(route('gardens.show', $garden))->assertOk();
});

test('archiving toggles and deleting soft deletes', function () {
    $habitat = Habitat::factory()->for($this->teacher)->create();

    $this->actingAs($this->teacher)->patch(route('aquariums.archive', $habitat));
    expect($habitat->fresh()->isArchived())->toBeTrue();

    $this->actingAs($this->teacher)->patch(route('aquariums.archive', $habitat));
    expect($habitat->fresh()->isArchived())->toBeFalse();

    $this->actingAs($this->teacher)->delete(route('aquariums.destroy', $habitat))->assertRedirect(route('aquariums.index'));
    expect($habitat->fresh()->trashed())->toBeTrue();
});

test('the dashboard and habitat pages render', function () {
    $habitat = Habitat::factory()->for($this->teacher)->create();

    $this->actingAs($this->teacher)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->component('Dashboard')->where('totals.aquariums', 1));
    $this->actingAs($this->teacher)->get(route('aquariums.show', $habitat))
        ->assertInertia(fn (Assert $page) => $page->component('Habitats/Show')->has('growthRule.stages', 5));
    $this->actingAs($this->teacher)->get(route('aquariums.report', $habitat))
        ->assertInertia(fn (Assert $page) => $page->component('Reports/Class'));
});
