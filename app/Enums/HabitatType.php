<?php

namespace App\Enums;

enum HabitatType: string
{
    case Aquarium = 'aquarium';
    case Garden = 'garden';

    /**
     * The game asset collection this habitat draws its members from.
     */
    public function assetType(): GameAssetType
    {
        return match ($this) {
            self::Aquarium => GameAssetType::Fish,
            self::Garden => GameAssetType::Plant,
        };
    }

    /**
     * Presentation settings for this habitat type from config/habitats.php.
     *
     * @return array{label: string, singular: string, plural: string, route: string, member_noun: string, themes: array<string, string>, default_theme: string}
     */
    public function config(): array
    {
        return config('habitats.types.'.$this->value);
    }

    public function routePrefix(): string
    {
        return $this->config()['route'];
    }

    /**
     * @return array<int, string>
     */
    public function themeKeys(): array
    {
        return array_keys($this->config()['themes']);
    }
}
