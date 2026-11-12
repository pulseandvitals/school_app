<?php

namespace App\Models;

use App\Enums\HabitatType;
use App\Services\Growth\GrowthCalculator;
use Database\Factories\HabitatFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'description', 'theme', 'growth_rule_id'])]
class Habitat extends Model
{
    /** @use HasFactory<HabitatFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => HabitatType::class,
            'archived_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function growthRule(): BelongsTo
    {
        return $this->belongsTo(GrowthRule::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(HabitatMember::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    #[Scope]
    protected function ofType(Builder $query, HabitatType $type): Builder
    {
        return $query->where('type', $type);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * The rule this habitat grows by: its own choice, or the type default.
     */
    public function effectiveGrowthRule(): GrowthRule
    {
        return $this->growthRule ?? GrowthRule::defaultFor($this->type);
    }

    /**
     * Memoised calculator for rendering many members of this habitat.
     */
    public function growthCalculator(): GrowthCalculator
    {
        return $this->growthCalculatorCache ??= $this->effectiveGrowthRule()->calculator();
    }

    private ?GrowthCalculator $growthCalculatorCache = null;
}
