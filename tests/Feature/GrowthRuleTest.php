<?php

use App\Enums\HabitatType;
use App\Models\Assessment;
use App\Models\GrowthRule;
use App\Models\Habitat;
use App\Models\HabitatMember;
use App\Models\User;
use App\Services\Growth\ScoreRecorder;

beforeEach(function () {
    $this->teacher = User::factory()->create();
});

function rulePayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Generous',
        'habitat_type' => 'aquarium',
        'point_bands' => [['min' => 0, 'points' => 1], ['min' => 50, 'points' => 5]],
        'stages' => [
            ['name' => 'Tiny', 'min_points' => 0, 'scale' => 0.5],
            ['name' => 'Big', 'min_points' => 5, 'scale' => 1.2],
        ],
    ], $overrides);
}

test('a teacher creates a growth rule; stages are ordered and numbered', function () {
    $this->actingAs($this->teacher)
        ->post(route('growth-rules.store'), rulePayload(['stages' => [
            ['name' => 'Big', 'min_points' => 5, 'scale' => 1.2],
            ['name' => 'Tiny', 'min_points' => 0, 'scale' => 0.5],
        ]]))
        ->assertRedirect(route('growth-rules.index'));

    $rule = GrowthRule::whereBelongsTo($this->teacher)->sole();
    expect($rule->habitat_type)->toBe(HabitatType::Aquarium)
        ->and(array_column($rule->stages, 'name'))->toBe(['Tiny', 'Big'])
        ->and(array_column($rule->stages, 'stage'))->toBe([1, 2]);
});

test('rules must cover 0% and start at 0 points', function () {
    $this->actingAs($this->teacher)
        ->post(route('growth-rules.store'), rulePayload([
            'point_bands' => [['min' => 50, 'points' => 5]],
            'stages' => [['name' => 'A', 'min_points' => 3, 'scale' => 1], ['name' => 'B', 'min_points' => 9, 'scale' => 1]],
        ]))
        ->assertSessionHasErrors([
            'point_bands' => 'Add a band starting at 0% so every score is covered.',
            'stages' => 'The first stage must start at 0 points.',
        ]);
});

test('system default rules cannot be edited', function () {
    $default = GrowthRule::defaultFor(HabitatType::Aquarium);
    $payload = rulePayload();
    unset($payload['habitat_type']);

    $this->actingAs($this->teacher)
        ->put(route('growth-rules.update', $default), $payload)
        ->assertNotFound();

    expect($default->fresh()->name)->toBe('Default Fish Growth');
});

test('editing a rule recalculates habitats that use it', function () {
    $rule = GrowthRule::factory()->for($this->teacher)->create();
    $habitat = Habitat::factory()->for($this->teacher)->create(['growth_rule_id' => $rule->id]);
    $member = HabitatMember::factory()->for($habitat)->create();
    $assessment = Assessment::factory()->for($habitat)->create(['total_score' => 10]);
    app(ScoreRecorder::class)->record($assessment, [$member->id => 6], $this->teacher);
    expect($member->fresh()->growth_points)->toBe(4);

    $payload = rulePayload();
    unset($payload['habitat_type']);

    $this->actingAs($this->teacher)
        ->put(route('growth-rules.update', $rule), $payload)
        ->assertSessionHasNoErrors();

    expect($member->fresh()->growth_points)->toBe(5)
        ->and($member->fresh()->growth_stage)->toBe(2);
});

test('deleting a rule falls back to the default and recalculates', function () {
    $rule = GrowthRule::factory()->for($this->teacher)->create(['point_bands' => [['min' => 0, 'points' => 1]]]);
    $habitat = Habitat::factory()->for($this->teacher)->create(['growth_rule_id' => $rule->id]);
    $member = HabitatMember::factory()->for($habitat)->create();
    $assessment = Assessment::factory()->for($habitat)->create(['total_score' => 10]);
    app(ScoreRecorder::class)->record($assessment, [$member->id => 10], $this->teacher);
    expect($member->fresh()->growth_points)->toBe(1);

    $this->actingAs($this->teacher)->delete(route('growth-rules.destroy', $rule));

    expect($habitat->fresh()->growth_rule_id)->toBeNull()
        ->and($member->fresh()->growth_points)->toBe(10);
});
