<?php

namespace Database\Seeders;

use App\Enums\GameAssetType;
use App\Models\GameAsset;
use Illuminate\Database\Seeder;

/**
 * Registers the 20 fish and 20 plant images. Safe to re-run: existing rows
 * keep their is_active flag and any stage_images a teacher has configured.
 */
class GameAssetSeeder extends Seeder
{
    public const ASSETS_PER_TYPE = 20;

    public function run(): void
    {
        foreach (GameAssetType::cases() as $type) {
            for ($number = 1; $number <= self::ASSETS_PER_TYPE; $number++) {
                GameAsset::query()->firstOrCreate(
                    ['type' => $type, 'asset_number' => $number],
                    [
                        'name' => ucfirst($type->value).' '.$number,
                        'image_path' => $type->directory().'/'.$type->value.$number.'.png',
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
