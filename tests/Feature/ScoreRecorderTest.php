<?php

use App\Enums\GrowthChangeReason;
use App\Models\Achievement;
use App\Models\Assessment;
use App\Models\Habitat;
use App\Models\HabitatMember;
use App\Services\Growth\GrowthRecalculator;
use App\Services\Growth\ScoreRecorder;
use Database\Seeders\AchievementSeeder;

beforeEach(function () {
    $this->habitat = Habitat::factory()->create();
    $this->teacher = $this->habitat->user;
    $this->member = HabitatMember::factory()->for($this->habitat)->create();
    $this->assessment = Assessment::factory()->for($this->habitat)->create(['total_score' => 20, 'title' => 'Math Quiz 1']);
    $this->recorder = app(ScoreRecorder::class);
});

test('recording a score stores the percentage, awards points and logs why', function () {
    $this->recorder->record($this->assessment, [$this->member->id => 18], $this->teacher);

    $score = $this->member->scores()->sole();
    expect((float) $score->percentage)->toBe(90.0)
        ->and($score->growth_points_awarded)->toBe(10)
        ->and($this->member->fresh()->growth_points)->toBe(10);

    $history = $this->member->growthHistories()->sole();
    expect($history->reason)->toBe(GrowthChangeReason::ScoreRecorded)
        ->and($history->delta)->toBe(10)
        ->and($history->description)->toBe('Math Quiz 1: 18/20 (90%) → +10 points');
});

test('resubmitting the same scores never adds growth twice', function () {
    $this->recorder->record($this->assessment, [$this->member->id => 18], $this->teacher);
    $summary = $this->recorder->record($this->assessment, [$this->member->id => '18.00'], $this->teacher);

    expect($summary['unchanged'])->toBe(1)
        ->and($this->member->scores()->count())->toBe(1)
        ->and($this->member->growthHistories()->count())->toBe(1)
        ->and($this->member->fresh()->growth_points)->toBe(10);
});

test('correcting a score recalculates growth and can lower the stage', function () {
    $this->member->forceFill(['growth_points' => 0])->save();
    $assessments = Assessment::factory()->for($this->habitat)->count(2)->create(['total_score' => 10]);
    $this->recorder->record($assessments[0], [$this->member->id => 10], $this->teacher);
    $this->recorder->record($assessments[1], [$this->member->id => 10], $this->teacher);
    expect($this->member->fresh()->growth_stage)->toBe(2);

    $this->recorder->record($assessments[1], [$this->member->id => 4], $this->teacher);

    $member = $this->member->fresh();
    $latest = $member->growthHistories()->latest('id')->first();
    expect($member->growth_points)->toBe(10)
        ->and($member->growth_stage)->toBe(1)
        ->and($latest->reason)->toBe(GrowthChangeReason::ScoreUpdated)
        ->and($latest->delta)->toBe(-10)
        ->and($latest->description)->toContain('corrected from +10');
});

test('clearing a score removes it and its growth', function () {
    $this->recorder->record($this->assessment, [$this->member->id => 18], $this->teacher);
    $this->recorder->record($this->assessment, [$this->member->id => null], $this->teacher);

    expect($this->member->scores()->count())->toBe(0)
        ->and($this->member->fresh()->growth_points)->toBe(0)
        ->and($this->member->growthHistories()->latest('id')->first()->reason)->toBe(GrowthChangeReason::ScoreRemoved);
});

test('members of other habitats are ignored', function () {
    $outsider = HabitatMember::factory()->create();

    $this->recorder->record($this->assessment, [$outsider->id => 20], $this->teacher);

    expect($outsider->scores()->count())->toBe(0)
        ->and($outsider->fresh()->growth_points)->toBe(0);
});

test('stage ups are reported back to the caller', function () {
    $this->member->forceFill(['growth_points' => 0])->save();
    $first = Assessment::factory()->for($this->habitat)->create(['total_score' => 10]);
    $this->recorder->record($first, [$this->member->id => 10], $this->teacher);

    $summary = $this->recorder->record($this->assessment, [$this->member->id => 20], $this->teacher);

    expect($summary['stage_ups'])->toHaveCount(1)
        ->and($summary['stage_ups'][0]->stage_after)->toBe(2);
});

test('achievements are awarded once when their criteria are met', function () {
    $this->seed(AchievementSeeder::class);

    $this->recorder->record($this->assessment, [$this->member->id => 20], $this->teacher);
    $this->recorder->record($this->assessment, [$this->member->id => 19], $this->teacher);
    $this->recorder->record($this->assessment, [$this->member->id => 20], $this->teacher);

    expect($this->member->achievements()->pluck('key')->sort()->values()->all())
        ->toBe(['first-assessment', 'first-perfect-score']);
});

test('a garden-only achievement is not awarded in an aquarium', function () {
    Achievement::factory()->create([
        'key' => 'garden-only',
        'habitat_type' => 'garden',
        'criteria' => ['type' => 'assessments_completed', 'count' => 1],
    ]);

    $this->recorder->record($this->assessment, [$this->member->id => 20], $this->teacher);

    expect($this->member->achievements()->count())->toBe(0);
});

test('changing the growth rule re-scores existing assessments', function () {
    $this->recorder->record($this->assessment, [$this->member->id => 18], $this->teacher);
    $rule = $this->habitat->effectiveGrowthRule()->replicate();
    $rule->user_id = $this->teacher->id;
    $rule->point_bands = [['min' => 0, 'points' => 3]];
    $rule->save();

    $this->habitat->update(['growth_rule_id' => $rule->id]);
    app(GrowthRecalculator::class)->reapplyRule($this->habitat);

    expect($this->member->scores()->sole()->growth_points_awarded)->toBe(3)
        ->and($this->member->fresh()->growth_points)->toBe(3)
        ->and($this->member->growthHistories()->latest('id')->first()->reason)->toBe(GrowthChangeReason::RulesChanged);
});
