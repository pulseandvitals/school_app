<?php

namespace Database\Factories;

use App\Enums\GameAssetType;
use App\Models\GameAsset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameAsset>
 */
class GameAssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $number = fake()->unique()->numberBetween(1, 100000);

        return [
            'type' => GameAssetType::Fish,
            'name' => 'Fish '.$number,
            'image_path' => 'images/fishes/fish'.$number.'.png',
            'asset_number' => $number,
            'is_active' => true,
        ];
    }

    public function plant(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => GameAssetType::Plant,
            'name' => 'Plant '.$attributes['asset_number'],
            'image_path' => 'images/plants/plant'.$attributes['asset_number'].'.png',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
