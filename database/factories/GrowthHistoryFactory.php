<?php

namespace Database\Factories;

use App\Enums\GrowthChangeReason;
use App\Models\GrowthHistory;
use App\Models\HabitatMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GrowthHistory>
 */
class GrowthHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'habitat_member_id' => HabitatMember::factory(),
            'reason' => GrowthChangeReason::ScoreRecorded,
            'points_before' => 0,
            'points_after' => 10,
            'delta' => 10,
            'stage_before' => 1,
            'stage_after' => 1,
        ];
    }
}
