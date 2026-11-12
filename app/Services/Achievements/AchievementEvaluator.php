<?php

namespace App\Services\Achievements;

use App\Models\Achievement;
use App\Models\AssessmentScore;
use App\Models\HabitatMember;
use App\Models\StudentAchievement;
use App\Services\Growth\GrowthCalculator;
use Illuminate\Support\Collection;

/**
 * Awards achievements whose JSON criteria a member now satisfies.
 * Achievements, once earned, are kept.
 *
 * Supported criteria types:
 *  - assessments_completed {count}
 *  - perfect_scores {count}
 *  - streak {count, min_percentage}           consecutive assessments by date
 *  - average {min_percentage, min_assessments}
 *  - stage_reached {stage: int|"max"}
 *  - points_within_days {points, days}         relative to the latest assessment
 */
class AchievementEvaluator
{
    /**
     * @return Collection<int, Achievement> Newly awarded achievements.
     */
    public function evaluate(HabitatMember $member, GrowthCalculator $calculator, ?AssessmentScore $trigger = null): Collection
    {
        $earnedIds = $member->studentAchievements()->pluck('achievement_id');

        $candidates = Achievement::query()
            ->applicableTo($member->habitat->type)
            ->whereNotIn('id', $earnedIds)
            ->get();

        if ($candidates->isEmpty()) {
            return collect();
        }

        $history = $member->scores()
            ->join('assessments', 'assessments.id', '=', 'assessment_scores.assessment_id')
            ->orderBy('assessments.assessed_on')
            ->orderBy('assessment_scores.id')
            ->get(['assessment_scores.percentage', 'assessment_scores.growth_points_awarded', 'assessments.assessed_on'])
            ->map(fn ($row): array => [
                'percentage' => (float) $row->percentage,
                'points' => (int) $row->growth_points_awarded,
                'date' => substr((string) $row->assessed_on, 0, 10),
            ]);

        $awarded = $candidates->filter(
            fn (Achievement $achievement): bool => $this->meets($achievement->criteria, $member, $calculator, $history),
        );

        foreach ($awarded as $achievement) {
            StudentAchievement::query()->firstOrCreate(
                ['habitat_member_id' => $member->id, 'achievement_id' => $achievement->id],
                ['assessment_score_id' => $trigger?->id, 'awarded_at' => now()],
            );
        }

        return $awarded->values();
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @param  Collection<int, array{percentage: float, points: int, date: string}>  $history
     */
    private function meets(array $criteria, HabitatMember $member, GrowthCalculator $calculator, Collection $history): bool
    {
        return match ($criteria['type'] ?? null) {
            'assessments_completed' => $history->count() >= $criteria['count'],
            'perfect_scores' => $history->where('percentage', '>=', 100)->count() >= $criteria['count'],
            'streak' => $this->longestStreak($history, (float) $criteria['min_percentage']) >= $criteria['count'],
            'average' => $history->count() >= $criteria['min_assessments']
                && $history->avg('percentage') >= $criteria['min_percentage'],
            'stage_reached' => $member->growth_stage >= ($criteria['stage'] === 'max' ? $calculator->maxStage() : (int) $criteria['stage']),
            'points_within_days' => $this->pointsInRecentDays($history, (int) $criteria['days']) >= $criteria['points'],
            default => false,
        };
    }

    /**
     * @param  Collection<int, array{percentage: float, points: int, date: string}>  $history
     */
    private function longestStreak(Collection $history, float $minPercentage): int
    {
        $longest = 0;
        $current = 0;

        foreach ($history as $entry) {
            $current = $entry['percentage'] >= $minPercentage ? $current + 1 : 0;
            $longest = max($longest, $current);
        }

        return $longest;
    }

    /**
     * @param  Collection<int, array{percentage: float, points: int, date: string}>  $history
     */
    private function pointsInRecentDays(Collection $history, int $days): int
    {
        if ($history->isEmpty()) {
            return 0;
        }

        $windowStart = date('Y-m-d', strtotime($history->last()['date'].' -'.$days.' days'));

        return $history->filter(fn (array $entry): bool => $entry['date'] > $windowStart)->sum('points');
    }
}
