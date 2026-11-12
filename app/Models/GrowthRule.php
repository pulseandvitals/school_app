<?php

namespace App\Models;

use App\Enums\HabitatType;
use App\Services\Growth\GrowthCalculator;
use Database\Factories\GrowthRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['habitat_type', 'name', 'point_bands', 'stages'])]
class GrowthRule extends Model
{
    /** @use HasFactory<GrowthRuleFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'habitat_type' => HabitatType::class,
            'point_bands' => 'array',
            'stages' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function habitats(): HasMany
    {
        return $this->hasMany(Habitat::class);
    }

    /**
     * Rules a teacher may attach to a habitat: system defaults plus their own.
     */
    #[Scope]
    protected function availableTo(Builder $query, User $user): Builder
    {
        return $query->where(fn (Builder $query) => $query->whereNull('user_id')->orWhereBelongsTo($user));
    }

    public function isSystemDefault(): bool
    {
        return $this->user_id === null;
    }

    public function calculator(): GrowthCalculator
    {
        return new GrowthCalculator($this->point_bands, $this->stages);
    }

    /**
     * The system-wide default rule for a habitat type, created from
     * config/growth.php on first use.
     */
    public static function defaultFor(HabitatType $type): self
    {
        $defaults = config('growth.defaults.'.$type->value);

        return static::query()->firstOrCreate(
            ['user_id' => null, 'habitat_type' => $type],
            [
                'name' => $defaults['name'],
                'point_bands' => $defaults['point_bands'],
                'stages' => $defaults['stages'],
            ],
        );
    }
}
