<?php

namespace Database\Seeders;

use App\Enums\HabitatType;
use App\Models\GrowthRule;
use Illuminate\Database\Seeder;

class GrowthRuleSeeder extends Seeder
{
    /**
     * Ensure every habitat type has a system default growth rule.
     */
    public function run(): void
    {
        foreach (HabitatType::cases() as $type) {
            GrowthRule::defaultFor($type);
        }
    }
}
