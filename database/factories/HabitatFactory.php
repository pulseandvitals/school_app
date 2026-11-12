<?php

namespace Database\Factories;

use App\Enums\HabitatType;
use App\Models\Habitat;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Habitat>
 */
class HabitatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => HabitatType::Aquarium,
            'name' => 'Grade '.fake()->numberBetween(1, 6).' - Section '.fake()->randomElement(['A', 'B', 'C']),
            'description' => fake()->optional()->sentence(),
            'theme' => 'coral-reef',
        ];
    }

    public function garden(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => HabitatType::Garden,
            'theme' => 'sunny-meadow',
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => now(),
        ]);
    }
}
