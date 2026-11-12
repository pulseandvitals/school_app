<?php

use App\Enums\GrowthChangeReason;
use App\Models\Assessment;
use App\Models\Habitat;
use App\Models\HabitatMember;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->habitat = Habitat::factory()->garden()->create();
    $this->teacher = $this->habitat->user;
    $this->member = HabitatMember::factory()->for($this->habitat)->create();
});

test('a teacher creates an assessment and is taken to score entry', function () {
    $response = $this->actingAs($this->teacher)->post(route('gardens.assessments.store', $this->habitat), [
        'title' => 'Science Quiz 2',
        'type' => 'custom',
        'custom_type' => 'Recitation',
        'assessed_on' => '2026-11-10',
        'total_score' => 25,
    ]);

    $assessment = Assessment::sole();
    $response->assertRedirect(route('gardens.assessments.show', [$this->habitat, $assessment]));
    expect($assessment->type_label)->toBe('Recitation');
});

test('assessment fields are validated', function () {
    $this->actingAs($this->teacher)
        ->post(route('gardens.assessments.store', $this->habitat), ['type' => 'custom', 'total_score' => 0])
        ->assertSessionHasErrors(['title', 'custom_type', 'assessed_on', 'total_score']);
});

test('scores entered in the grid grow the plant', function () {
    $assessment = Assessment::factory()->for($this->habitat)->create(['total_score' => 20]);

    $this->actingAs($this->teacher)
        ->put(route('gardens.assessments.scores.update', [$this->habitat, $assessment]), ['scores' => [$this->member->id => 19]])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', 'Saved 1 score.');

    expect($this->member->fresh()->growth_points)->toBe(10);
});

test('a score above the total is rejected', function () {
    $assessment = Assessment::factory()->for($this->habitat)->create(['total_score' => 20]);

    $this->actingAs($this->teacher)
        ->put(route('gardens.assessments.scores.update', [$this->habitat, $assessment]), ['scores' => [$this->member->id => 21]])
        ->assertSessionHasErrors(["scores.{$this->member->id}" => 'A score cannot be higher than the total of 20.']);

    expect($this->member->scores()->count())->toBe(0);
});

test('reaching a new stage is flashed for the celebration', function () {
    $assessment = Assessment::factory()->for($this->habitat)->create(['total_score' => 10]);
    $this->member->forceFill(['growth_points' => 10])->save();
    Assessment::factory()->for($this->habitat)->create()->scores()->create([
        'habitat_member_id' => $this->member->id, 'score' => 10, 'percentage' => 100, 'growth_points_awarded' => 10,
    ]);

    $this->actingAs($this->teacher)
        ->put(route('gardens.assessments.scores.update', [$this->habitat, $assessment]), ['scores' => [$this->member->id => 10]])
        ->assertSessionHas('celebrations', [[
            'name' => $this->member->student->name,
            'stage' => 2,
            'stage_name' => 'Sprout',
        ]]);
});

test('another teacher cannot record scores', function () {
    $assessment = Assessment::factory()->for($this->habitat)->create();

    $this->actingAs(User::factory()->create())
        ->put(route('gardens.assessments.scores.update', [$this->habitat, $assessment]), ['scores' => [$this->member->id => 20]])
        ->assertNotFound();

    expect($this->member->scores()->count())->toBe(0);
});

test('an assessment from another garden cannot be reached through this one', function () {
    $otherAssessment = Assessment::factory()->for(Habitat::factory()->garden()->for($this->teacher))->create();

    $this->actingAs($this->teacher)
        ->get(route('gardens.assessments.show', [$this->habitat, $otherAssessment]))
        ->assertNotFound();
});

test('changing the total recalculates percentages and growth', function () {
    $assessment = Assessment::factory()->for($this->habitat)->create(['total_score' => 20, 'title' => 'Quiz', 'assessed_on' => '2026-11-01']);
    $this->actingAs($this->teacher)->put(route('gardens.assessments.scores.update', [$this->habitat, $assessment]), ['scores' => [$this->member->id => 10]]);
    expect($this->member->fresh()->growth_points)->toBe(2);

    $this->actingAs($this->teacher)->put(route('gardens.assessments.update', [$this->habitat, $assessment]), [
        'title' => 'Quiz', 'type' => 'quiz', 'assessed_on' => '2026-11-01', 'total_score' => 10,
    ])->assertSessionHasNoErrors();

    expect((float) $this->member->scores()->sole()->percentage)->toBe(100.0)
        ->and($this->member->fresh()->growth_points)->toBe(10);
});

test('deleting an assessment removes its growth and logs why', function () {
    $assessment = Assessment::factory()->for($this->habitat)->create(['total_score' => 10]);
    $this->actingAs($this->teacher)->put(route('gardens.assessments.scores.update', [$this->habitat, $assessment]), ['scores' => [$this->member->id => 10]]);

    $this->actingAs($this->teacher)
        ->delete(route('gardens.assessments.destroy', [$this->habitat, $assessment]))
        ->assertRedirect(route('gardens.assessments.index', $this->habitat));

    expect(Assessment::count())->toBe(0)
        ->and($this->member->fresh()->growth_points)->toBe(0)
        ->and($this->member->growthHistories()->latest('id')->first()->reason)->toBe(GrowthChangeReason::ScoreRemoved);
});

test('the score grid shows existing scores', function () {
    $assessment = Assessment::factory()->for($this->habitat)->create(['total_score' => 20]);
    $this->actingAs($this->teacher)->put(route('gardens.assessments.scores.update', [$this->habitat, $assessment]), ['scores' => [$this->member->id => 15]]);

    $this->actingAs($this->teacher)
        ->get(route('gardens.assessments.show', [$this->habitat, $assessment]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Assessments/Show')
            ->where("scores.{$this->member->id}.score", 15)
            ->where("scores.{$this->member->id}.points", 6));
});
