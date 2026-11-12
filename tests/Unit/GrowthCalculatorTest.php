<?php

use App\Services\Growth\GrowthCalculator;

function calculator(): GrowthCalculator
{
    return new GrowthCalculator(
        [
            ['min' => 0, 'points' => 0],
            ['min' => 90, 'points' => 10],
            ['min' => 80, 'points' => 8],
            ['min' => 50, 'points' => 2],
        ],
        [
            ['stage' => 2, 'name' => 'Young', 'min_points' => 20, 'scale' => 0.7],
            ['stage' => 1, 'name' => 'Baby', 'min_points' => 0, 'scale' => 0.5],
            ['stage' => 3, 'name' => 'Adult', 'min_points' => 40, 'scale' => 1.0],
        ],
    );
}

test('percentage is rounded and clamped to 0-100', function () {
    expect(calculator()->percentage(18, 20))->toBe(90.0)
        ->and(calculator()->percentage(2, 3))->toBe(66.67)
        ->and(calculator()->percentage(25, 20))->toBe(100.0);
});

test('a zero total score is rejected', function () {
    calculator()->percentage(5, 0);
})->throws(InvalidArgumentException::class);

test('points come from the highest band reached regardless of band order', function (float $percentage, int $points) {
    expect(calculator()->pointsForPercentage($percentage))->toBe($points);
})->with([
    [100, 10],
    [90, 10],
    [89.99, 8],
    [80, 8],
    [79, 2],
    [50, 2],
    [49.99, 0],
]);

test('stage boundaries follow min_points thresholds', function (int $points, int $stage) {
    expect(calculator()->stageForPoints($points))->toBe($stage);
})->with([
    [0, 1],
    [19, 1],
    [20, 2],
    [39, 2],
    [40, 3],
    [500, 3],
]);

test('progress towards the next stage is reported', function () {
    expect(calculator()->pointsToNextStage(25))->toBe(15)
        ->and(calculator()->stageProgress(30))->toBe(50)
        ->and(calculator()->pointsToNextStage(40))->toBeNull()
        ->and(calculator()->stageProgress(40))->toBe(100)
        ->and(calculator()->maxStage())->toBe(3);
});
