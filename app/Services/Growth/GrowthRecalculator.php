<?php

namespace App\Services\Growth;

use App\Enums\GrowthChangeReason;
use App\Models\AssessmentScore;
use App\Models\GrowthHistory;
use App\Models\Habitat;
use App\Models\HabitatMember;
use Illuminate\Support\Facades\DB;

/**
 * Derives a member's growth from its recorded scores (the source of truth)
 * and writes a history entry whenever the result changes. Because totals are
 * recomputed rather than incremented, repeating a request can never add
 * growth twice.
 */
class GrowthRecalculator
{
    public function recalculate(
        HabitatMember $member,
        GrowthCalculator $calculator,
        GrowthChangeReason $reason,
        ?AssessmentScore $score = null,
        ?string $description = null,
    ): ?GrowthHistory {
        $pointsBefore = $member->growth_points;
        $stageBefore = $member->growth_stage;

        $pointsAfter = (int) $member->scores()->sum('growth_points_awarded');
        $stageAfter = $calculator->stageForPoints($pointsAfter);

        if ($pointsAfter === $pointsBefore && $stageAfter === $stageBefore) {
            return null;
        }

        $member->forceFill([
            'growth_points' => $pointsAfter,
            'growth_stage' => $stageAfter,
        ])->save();

        return $member->growthHistories()->create([
            'assessment_score_id' => $score?->id,
            'reason' => $reason,
            'description' => $description,
            'points_before' => $pointsBefore,
            'points_after' => $pointsAfter,
            'delta' => $pointsAfter - $pointsBefore,
            'stage_before' => $stageBefore,
            'stage_after' => $stageAfter,
        ]);
    }

    /**
     * Re-score every assessment in a habitat with its current growth rule
     * and recalculate all members. Used when the rule or its settings change.
     */
    public function reapplyRule(Habitat $habitat): void
    {
        DB::transaction(function () use ($habitat): void {
            $habitat->unsetRelation('growthRule');
            $calculator = $habitat->effectiveGrowthRule()->calculator();

            AssessmentScore::query()
                ->whereIn('assessment_id', $habitat->assessments()->select('id'))
                ->lockForUpdate()
                ->get()
                ->each(function (AssessmentScore $score) use ($calculator): void {
                    $points = $calculator->pointsForPercentage((float) $score->percentage);

                    if ($points !== $score->growth_points_awarded) {
                        $score->update(['growth_points_awarded' => $points]);
                    }
                });

            $habitat->members()->lockForUpdate()->get()->each(
                fn (HabitatMember $member) => $this->recalculate(
                    $member,
                    $calculator,
                    GrowthChangeReason::RulesChanged,
                    description: 'Growth rules were updated',
                ),
            );
        });
    }
}
