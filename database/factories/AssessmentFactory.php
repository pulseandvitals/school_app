<?php

namespace Database\Factories;

use App\Enums\AssessmentType;
use App\Models\Assessment;
use App\Models\Habitat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assessment>
 */
class AssessmentFactory extends Factory
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
            'title' => fake()->randomElement(['Mathematics', 'Science', 'English', 'Filipino']).' Quiz '.fake()->numberBetween(1, 9),
            'type' => AssessmentType::Quiz,
            'assessed_on' => fake()->dateTimeBetween('-1 month')->format('Y-m-d'),
            'total_score' => 20,
        ];
    }
}
