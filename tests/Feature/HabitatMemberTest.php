<?php

use App\Models\Assessment;
use App\Models\GameAsset;
use App\Models\Habitat;
use App\Models\HabitatMember;
use App\Models\Student;
use App\Services\Growth\ScoreRecorder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->habitat = Habitat::factory()->create();
    $this->teacher = $this->habitat->user;
    $this->fish = GameAsset::factory()->create();
});

test('a new student is added with their chosen fish and photo', function () {
    Storage::fake('public');

    $this->actingAs($this->teacher)
        ->post(route('aquariums.members.store', $this->habitat), [
            'name' => 'Anna Cruz',
            'student_number' => 'S-001',
            'photo' => UploadedFile::fake()->image('anna.jpg'),
            'game_asset_id' => $this->fish->id,
        ])
        ->assertRedirect(route('aquariums.show', $this->habitat));

    $member = $this->habitat->members()->sole();
    expect($member->student->name)->toBe('Anna Cruz')
        ->and($member->student->user_id)->toBe($this->teacher->id)
        ->and($member->game_asset_id)->toBe($this->fish->id);
    Storage::disk('public')->assertExists($member->student->photo_path);
});

test('an existing roster student can join', function () {
    $student = Student::factory()->for($this->teacher)->create();

    $this->actingAs($this->teacher)
        ->post(route('aquariums.members.store', $this->habitat), ['student_id' => $student->id, 'game_asset_id' => $this->fish->id])
        ->assertSessionHasNoErrors();

    expect($this->habitat->members()->sole()->student_id)->toBe($student->id);
});

test('another teacher\'s student cannot be added', function () {
    $foreignStudent = Student::factory()->create();

    $this->actingAs($this->teacher)
        ->post(route('aquariums.members.store', $this->habitat), ['student_id' => $foreignStudent->id, 'game_asset_id' => $this->fish->id])
        ->assertSessionHasErrors('student_id');
});

test('a student cannot be added twice to the same aquarium', function () {
    $member = HabitatMember::factory()->for($this->habitat)->create();

    $this->actingAs($this->teacher)
        ->post(route('aquariums.members.store', $this->habitat), ['student_id' => $member->student_id, 'game_asset_id' => $this->fish->id])
        ->assertSessionHasErrors(['student_id' => 'This student is already in this aquarium.']);
});

test('only active assets of the habitat\'s type can be chosen', function (GameAsset $asset) {
    $this->actingAs($this->teacher)
        ->post(route('aquariums.members.store', $this->habitat), ['name' => 'Ben', 'game_asset_id' => $asset->id])
        ->assertSessionHasErrors('game_asset_id');
})->with([
    'inactive fish' => fn () => GameAsset::factory()->inactive()->create(),
    'plant in an aquarium' => fn () => GameAsset::factory()->plant()->create(),
]);

test('the selector only offers active assets of the right type', function () {
    GameAsset::factory()->inactive()->create();
    GameAsset::factory()->plant()->create();

    $this->actingAs($this->teacher)
        ->get(route('aquariums.members.create', $this->habitat))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Members/Form')
            ->has('assets', 1)
            ->where('assets.0.id', $this->fish->id));
});

test('changing the fish keeps scores and growth', function () {
    $member = HabitatMember::factory()->for($this->habitat)->create();
    $assessment = Assessment::factory()->for($this->habitat)->create(['total_score' => 10]);
    app(ScoreRecorder::class)->record($assessment, [$member->id => 10], $this->teacher);
    $newFish = GameAsset::factory()->create();

    $this->actingAs($this->teacher)
        ->post(route('aquariums.members.update', [$this->habitat, $member]), [
            'name' => $member->student->name,
            'game_asset_id' => $newFish->id,
        ])
        ->assertSessionHasNoErrors();

    $member->refresh();
    expect($member->game_asset_id)->toBe($newFish->id)
        ->and($member->growth_points)->toBe(10)
        ->and($member->scores()->count())->toBe(1);
});

test('a member keeps a retired fish when editing other details', function () {
    $retired = GameAsset::factory()->inactive()->create();
    $member = HabitatMember::factory()->for($this->habitat)->create(['game_asset_id' => $retired->id]);

    $this->actingAs($this->teacher)
        ->post(route('aquariums.members.update', [$this->habitat, $member]), ['name' => 'Renamed', 'game_asset_id' => $retired->id])
        ->assertSessionHasNoErrors();

    expect($member->student->fresh()->name)->toBe('Renamed');
});

test('a removed student gets their history back when re-added', function () {
    $member = HabitatMember::factory()->for($this->habitat)->create();
    $assessment = Assessment::factory()->for($this->habitat)->create(['total_score' => 10]);
    app(ScoreRecorder::class)->record($assessment, [$member->id => 9], $this->teacher);

    $this->actingAs($this->teacher)->delete(route('aquariums.members.destroy', [$this->habitat, $member]));
    expect($this->habitat->members()->count())->toBe(0);

    $this->actingAs($this->teacher)
        ->post(route('aquariums.members.store', $this->habitat), ['student_id' => $member->student_id, 'game_asset_id' => $this->fish->id])
        ->assertSessionHasNoErrors();

    $restored = $this->habitat->members()->sole();
    expect($restored->id)->toBe($member->id)
        ->and($restored->growth_points)->toBe(10)
        ->and($restored->game_asset_id)->toBe($this->fish->id);
});

test('a member from another aquarium cannot be reached through this one', function () {
    $otherHabitat = Habitat::factory()->for($this->teacher)->create();
    $otherMember = HabitatMember::factory()->for($otherHabitat)->create();

    $this->actingAs($this->teacher)
        ->get(route('aquariums.members.show', [$this->habitat, $otherMember]))
        ->assertNotFound();
});

test('another teacher cannot add students to this aquarium', function () {
    $this->actingAs(Student::factory()->create()->user)
        ->post(route('aquariums.members.store', $this->habitat), ['name' => 'Intruder', 'game_asset_id' => $this->fish->id])
        ->assertNotFound();

    expect(Student::where('name', 'Intruder')->exists())->toBeFalse();
});

test('the student report explains growth', function () {
    $member = HabitatMember::factory()->for($this->habitat)->create();
    $assessment = Assessment::factory()->for($this->habitat)->create(['total_score' => 20, 'title' => 'Math Quiz 1']);
    app(ScoreRecorder::class)->record($assessment, [$member->id => 18], $this->teacher);

    $this->actingAs($this->teacher)
        ->get(route('aquariums.members.show', [$this->habitat, $member]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Members/Show')
            ->where('stats.completed', 1)
            ->where('stats.average', 90)
            ->where('member.growth_points', 10)
            ->where('member.growth.points_to_next', 10)
            ->where('timeline.0.description', 'Math Quiz 1: 18/20 (90%) → +10 points'));
});
