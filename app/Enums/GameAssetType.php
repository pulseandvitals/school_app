<?php

namespace App\Enums;

enum GameAssetType: string
{
    case Fish = 'fish';
    case Plant = 'plant';

    /**
     * Public directory (relative to /public) where this asset type's images live.
     */
    public function directory(): string
    {
        return match ($this) {
            self::Fish => 'images/fishes',
            self::Plant => 'images/plants',
        };
    }
}
