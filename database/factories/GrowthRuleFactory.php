<?php

namespace Database\Factories;

use App\Enums\HabitatType;
use App\Models\GrowthRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GrowthRule>
 */
class GrowthRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $defaults = config('growth.defaults.aquarium');

        return [
            'user_id' => User::factory(),
            'habitat_type' => HabitatType::Aquarium,
            'name' => fake()->words(3, true),
            'point_bands' => $defaults['point_bands'],
            'stages' => $defaults['stages'],
        ];
    }
}
