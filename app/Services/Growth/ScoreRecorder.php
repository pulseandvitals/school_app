<?php

namespace App\Services\Growth;

use App\Enums\GrowthChangeReason;
use App\Models\ActivityLog;
use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\GrowthHistory;
use App\Models\User;
use App\Services\Achievements\AchievementEvaluator;
use Illuminate\Support\Facades\DB;

/**
 * Records a batch of scores for one assessment and updates growth — the
 * single entry point for score changes in every habitat type.
 */
class ScoreRecorder
{
    public function __construct(
        private GrowthRecalculator $recalculator,
        private AchievementEvaluator $achievements,
    ) {}

    /**
     * @param  array<int, float|int|string|null>  $scores  Raw scores keyed by habitat_member_id. Null removes a score.
     * @return array{recorded: int, updated: int, removed: int, unchanged: int, stage_ups: array<int, GrowthHistory>}
     */
    public function record(Assessment $assessment, array $scores, User $recordedBy): array
    {
        return DB::transaction(function () use ($assessment, $scores, $recordedBy): array {
            // Serialise concurrent saves of the same assessment.
            $assessment = Assessment::query()->lockForUpdate()->findOrFail($assessment->id);
            $habitat = $assessment->habitat;
            $calculator = $habitat->effectiveGrowthRule()->calculator();
            $totalScore = (float) $assessment->total_score;

            $members = $habitat->members()
                ->whereKey(array_keys($scores))
                ->lockForUpdate()
                ->get();

            $existingScores = $assessment->scores()
                ->whereIn('habitat_member_id', $members->modelKeys())
                ->get()
                ->keyBy('habitat_member_id');

            $summary = ['recorded' => 0, 'updated' => 0, 'removed' => 0, 'unchanged' => 0, 'stage_ups' => []];

            foreach ($members as $member) {
                $rawScore = $scores[$member->id];
                $existing = $existingScores->get($member->id);

                if ($rawScore === null || $rawScore === '') {
                    if ($existing) {
                        $existing->delete();
                        $this->recalculator->recalculate(
                            $member,
                            $calculator,
                            GrowthChangeReason::ScoreRemoved,
                            description: "{$assessment->title}: score removed (was +{$existing->growth_points_awarded})",
                        );
                        $summary['removed']++;
                    }

                    continue;
                }

                $rawScore = (float) $rawScore;
                $percentage = $calculator->percentage($rawScore, $totalScore);
                $points = $calculator->pointsForPercentage($percentage);

                if ($existing
                    && (float) $existing->score === $rawScore
                    && (float) $existing->percentage === $percentage
                    && $existing->growth_points_awarded === $points) {
                    $summary['unchanged']++;

                    continue;
                }

                $score = $existing ?? new AssessmentScore([
                    'assessment_id' => $assessment->id,
                    'habitat_member_id' => $member->id,
                ]);
                $previousPoints = $existing?->growth_points_awarded;

                $score->fill([
                    'score' => $rawScore,
                    'percentage' => $percentage,
                    'growth_points_awarded' => $points,
                    'recorded_by' => $recordedBy->id,
                ])->save();

                $history = $this->recalculator->recalculate(
                    $member,
                    $calculator,
                    $existing ? GrowthChangeReason::ScoreUpdated : GrowthChangeReason::ScoreRecorded,
                    $score,
                    $this->describe($assessment, $rawScore, $percentage, $points, $previousPoints),
                );

                $summary[$existing ? 'updated' : 'recorded']++;

                if ($history?->isStageUp()) {
                    $summary['stage_ups'][] = $history;
                }

                $this->achievements->evaluate($member, $calculator, $score);
            }

            if ($summary['recorded'] + $summary['updated'] + $summary['removed'] > 0) {
                ActivityLog::record($recordedBy, 'scores.recorded', $assessment, array_diff_key($summary, ['stage_ups' => true]));
            }

            return $summary;
        });
    }

    private function describe(Assessment $assessment, float $score, float $percentage, int $points, ?int $previousPoints): string
    {
        $text = sprintf(
            '%s: %s/%s (%s%%) → +%d points',
            $assessment->title,
            $this->number($score),
            $this->number((float) $assessment->total_score),
            $this->number($percentage),
            $points,
        );

        return $previousPoints === null ? $text : "{$text}, corrected from +{$previousPoints}";
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
