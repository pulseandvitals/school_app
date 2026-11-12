<?php

namespace App\Services\Growth;

use InvalidArgumentException;

/**
 * Pure growth maths: no database access, so it is trivially testable and
 * shared by every habitat type.
 */
class GrowthCalculator
{
    /**
     * @var array<int, array{min: float|int, points: int}>
     */
    private array $pointBands;

    /**
     * @var array<int, array{stage: int, name: string, min_points: int, scale: float}>
     */
    private array $stages;

    /**
     * @param  array<int, array{min: float|int, points: int}>  $pointBands
     * @param  array<int, array{stage: int, name: string, min_points: int, scale: float}>  $stages
     */
    public function __construct(array $pointBands, array $stages)
    {
        if ($pointBands === [] || $stages === []) {
            throw new InvalidArgumentException('Growth rules need at least one point band and one stage.');
        }

        usort($pointBands, fn (array $a, array $b): int => $b['min'] <=> $a['min']);
        usort($stages, fn (array $a, array $b): int => $a['min_points'] <=> $b['min_points']);

        $this->pointBands = array_values($pointBands);
        $this->stages = array_values($stages);
    }

    /**
     * Percentage (0-100, two decimals) of a raw score.
     */
    public function percentage(float $score, float $totalScore): float
    {
        if ($totalScore <= 0) {
            throw new InvalidArgumentException('Total score must be greater than zero.');
        }

        return round(min(max($score / $totalScore * 100, 0), 100), 2);
    }

    /**
     * Growth points earned for a single assessment percentage.
     */
    public function pointsForPercentage(float $percentage): int
    {
        foreach ($this->pointBands as $band) {
            if ($percentage >= $band['min']) {
                return (int) $band['points'];
            }
        }

        return 0;
    }

    /**
     * Stage number reached with the given accumulated points.
     */
    public function stageForPoints(int $points): int
    {
        return $this->stageDefinitionForPoints($points)['stage'];
    }

    /**
     * @return array{stage: int, name: string, min_points: int, scale: float}
     */
    public function stageDefinitionForPoints(int $points): array
    {
        $reached = $this->stages[0];

        foreach ($this->stages as $stage) {
            if ($points >= $stage['min_points']) {
                $reached = $stage;
            }
        }

        return $reached;
    }

    /**
     * Points still needed for the next stage, or null at the final stage.
     */
    public function pointsToNextStage(int $points): ?int
    {
        foreach ($this->stages as $stage) {
            if ($stage['min_points'] > $points) {
                return $stage['min_points'] - $points;
            }
        }

        return null;
    }

    /**
     * Progress (0-100) through the current stage towards the next one.
     */
    public function stageProgress(int $points): int
    {
        $current = $this->stageDefinitionForPoints($points);
        $toNext = $this->pointsToNextStage($points);

        if ($toNext === null) {
            return 100;
        }

        $span = ($points + $toNext) - $current['min_points'];

        return (int) floor(($points - $current['min_points']) / $span * 100);
    }

    public function maxStage(): int
    {
        return end($this->stages)['stage'];
    }

    /**
     * @return array<int, array{stage: int, name: string, min_points: int, scale: float}>
     */
    public function stages(): array
    {
        return $this->stages;
    }

    /**
     * @return array<int, array{min: float|int, points: int}>
     */
    public function pointBands(): array
    {
        return $this->pointBands;
    }
}
