<?php

namespace Database\Factories;

use App\Models\GameAsset;
use App\Models\Habitat;
use App\Models\HabitatMember;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HabitatMember>
 */
class HabitatMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'habitat_id' => Habitat::factory(),
            'student_id' => fn (array $attributes) => Student::factory()->state([
                'user_id' => Habitat::find($attributes['habitat_id'])->user_id,
            ]),
            'game_asset_id' => GameAsset::factory(),
            'growth_points' => 0,
            'growth_stage' => 1,
        ];
    }
}
