<?php

namespace App\Models;

use App\Enums\HabitatType;
use Database\Factories\AchievementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * criteria JSON shape: {"type": "<evaluator key>", ...evaluator params}
 */
#[Fillable(['key', 'name', 'description', 'icon', 'habitat_type', 'criteria', 'is_active'])]
class Achievement extends Model
{
    /** @use HasFactory<AchievementFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'habitat_type' => HabitatType::class,
            'criteria' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function studentAchievements(): HasMany
    {
        return $this->hasMany(StudentAchievement::class);
    }

    /**
     * Active achievements that apply to the given habitat type.
     */
    #[Scope]
    protected function applicableTo(Builder $query, HabitatType $type): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('habitat_type')->orWhere('habitat_type', $type));
    }
}
