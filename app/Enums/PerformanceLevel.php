<?php

namespace App\Enums;

enum PerformanceLevel: string
{
    case Excellent = 'excellent';
    case Good = 'good';
    case Fair = 'fair';
    case NeedsImprovement = 'needs_improvement';

    public static function fromAverage(?float $average): ?self
    {
        return match (true) {
            $average === null => null,
            $average >= 90 => self::Excellent,
            $average >= 75 => self::Good,
            $average >= 60 => self::Fair,
            default => self::NeedsImprovement,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Excellent => 'Excellent',
            self::Good => 'Good',
            self::Fair => 'Fair',
            self::NeedsImprovement => 'Needs improvement',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $level): array => ['value' => $level->value, 'label' => $level->label()], self::cases());
    }
}
