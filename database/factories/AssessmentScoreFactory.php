<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\HabitatMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssessmentScore>
 */
class AssessmentScoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assessment_id' => Assessment::factory(),
            'habitat_member_id' => HabitatMember::factory(),
            'score' => 18,
            'percentage' => 90,
            'growth_points_awarded' => 10,
        ];
    }
}
