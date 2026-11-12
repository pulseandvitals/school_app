<?php

namespace Database\Seeders;

use App\Enums\HabitatType;
use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    /**
     * Seed the starter achievements. Criteria are evaluated by
     * App\Services\Achievements\AchievementEvaluator.
     */
    public function run(): void
    {
        $achievements = [
            [
                'key' => 'first-assessment',
                'name' => 'First Splash',
                'description' => 'Completed the very first assessment.',
                'icon' => 'flag',
                'criteria' => ['type' => 'assessments_completed', 'count' => 1],
            ],
            [
                'key' => 'first-perfect-score',
                'name' => 'Perfect Score',
                'description' => 'Earned a perfect 100% on an assessment.',
                'icon' => 'star',
                'criteria' => ['type' => 'perfect_scores', 'count' => 1],
            ],
            [
                'key' => 'consistent-learner',
                'name' => 'Consistent Learner',
                'description' => 'Scored 75% or higher on 5 assessments in a row.',
                'icon' => 'repeat',
                'criteria' => ['type' => 'streak', 'count' => 5, 'min_percentage' => 75],
            ],
            [
                'key' => 'excellent-performance',
                'name' => 'Excellent Performance',
                'description' => 'Kept an average of 90% or higher across at least 5 assessments.',
                'icon' => 'trophy',
                'criteria' => ['type' => 'average', 'min_percentage' => 90, 'min_assessments' => 5],
            ],
            [
                'key' => 'fast-grower',
                'name' => 'Fast Grower',
                'description' => 'Gained 30 growth points within 14 days.',
                'icon' => 'zap',
                'criteria' => ['type' => 'points_within_days', 'points' => 30, 'days' => 14],
            ],
            [
                'key' => 'fully-grown-fish',
                'name' => 'Fully Grown Fish',
                'description' => 'Raised a fish all the way to its final growth stage.',
                'icon' => 'fish',
                'habitat_type' => HabitatType::Aquarium,
                'criteria' => ['type' => 'stage_reached', 'stage' => 'max'],
            ],
            [
                'key' => 'blooming-success',
                'name' => 'Blooming Success',
                'description' => 'Grew a plant all the way into full bloom.',
                'icon' => 'flower-2',
                'habitat_type' => HabitatType::Garden,
                'criteria' => ['type' => 'stage_reached', 'stage' => 'max'],
            ],
        ];

        foreach ($achievements as $achievement) {
            Achievement::query()->updateOrCreate(['key' => $achievement['key']], $achievement);
        }
    }
}
