<?php

/*
|--------------------------------------------------------------------------
| Default growth rules
|--------------------------------------------------------------------------
|
| These values seed the system-wide default growth rule for each habitat
| type. Teachers can create their own rules from the settings page; those
| are stored in the growth_rules table and take precedence.
|
| point_bands: the first band whose "min" percentage is met awards its points.
| stages: a member reaches a stage once its points are >= "min_points".
|         "scale" drives the visual size of the fish/plant in its scene.
|
*/

$pointBands = [
    ['min' => 90, 'points' => 10],
    ['min' => 80, 'points' => 8],
    ['min' => 70, 'points' => 6],
    ['min' => 60, 'points' => 4],
    ['min' => 50, 'points' => 2],
    ['min' => 0, 'points' => 0],
];

return [
    'defaults' => [
        'aquarium' => [
            'name' => 'Default Fish Growth',
            'point_bands' => $pointBands,
            'stages' => [
                ['stage' => 1, 'name' => 'Baby Fish', 'min_points' => 0, 'scale' => 0.55],
                ['stage' => 2, 'name' => 'Young Fish', 'min_points' => 20, 'scale' => 0.72],
                ['stage' => 3, 'name' => 'Growing Fish', 'min_points' => 40, 'scale' => 0.88],
                ['stage' => 4, 'name' => 'Adult Fish', 'min_points' => 60, 'scale' => 1.05],
                ['stage' => 5, 'name' => 'Mature Fish', 'min_points' => 80, 'scale' => 1.25],
            ],
        ],
        'garden' => [
            'name' => 'Default Plant Growth',
            'point_bands' => $pointBands,
            'stages' => [
                ['stage' => 1, 'name' => 'Seed', 'min_points' => 0, 'scale' => 0.35],
                ['stage' => 2, 'name' => 'Sprout', 'min_points' => 15, 'scale' => 0.5],
                ['stage' => 3, 'name' => 'Seedling', 'min_points' => 30, 'scale' => 0.65],
                ['stage' => 4, 'name' => 'Growing Plant', 'min_points' => 50, 'scale' => 0.82],
                ['stage' => 5, 'name' => 'Fully Grown Plant', 'min_points' => 70, 'scale' => 1.0],
                ['stage' => 6, 'name' => 'Flowering Plant', 'min_points' => 90, 'scale' => 1.18],
            ],
        ],
    ],
];
