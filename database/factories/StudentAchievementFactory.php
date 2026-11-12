<?php

namespace Database\Factories;

use App\Models\Achievement;
use App\Models\HabitatMember;
use App\Models\StudentAchievement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentAchievement>
 */
class StudentAchievementFactory extends Factory
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
            'achievement_id' => Achievement::factory(),
            'awarded_at' => now(),
        ];
    }
}
