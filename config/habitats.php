<?php

/*
|--------------------------------------------------------------------------
| Habitat (game environment) registry
|--------------------------------------------------------------------------
|
| Each key matches an App\Enums\HabitatType case. Adding a new environment
| means adding an enum case, an entry here, an asset collection and a Vue
| scene component — the classroom, scoring and growth logic is shared.
|
*/

return [
    'types' => [
        'aquarium' => [
            'label' => 'School of Fishes',
            'singular' => 'Aquarium',
            'plural' => 'Aquariums',
            'route' => 'aquariums',
            'member_noun' => 'fish',
            'default_theme' => 'coral-reef',
            'themes' => [
                'coral-reef' => 'Coral Reef',
                'deep-ocean' => 'Deep Ocean',
                'sunny-lagoon' => 'Sunny Lagoon',
                'kelp-forest' => 'Kelp Forest',
            ],
        ],
        'garden' => [
            'label' => 'Garden of Hope',
            'singular' => 'Garden',
            'plural' => 'Gardens',
            'route' => 'gardens',
            'member_noun' => 'plant',
            'default_theme' => 'sunny-meadow',
            'themes' => [
                'sunny-meadow' => 'Sunny Meadow',
                'spring-blossom' => 'Spring Blossom',
                'golden-sunset' => 'Golden Sunset',
                'zen-garden' => 'Zen Garden',
            ],
        ],
    ],
];
