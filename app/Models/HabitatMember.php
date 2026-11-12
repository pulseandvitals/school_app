<?php

namespace App\Models;

use Database\Factories\HabitatMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A student living in a habitat — the student's fish in an aquarium or
 * plant in a garden. Growth is tracked per membership.
 */
#[Fillable(['student_id', 'game_asset_id'])]
class HabitatMember extends Model
{
    /** @use HasFactory<HabitatMemberFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'growth_points' => 'integer',
            'growth_stage' => 'integer',
        ];
    }

    public function habitat(): BelongsTo
    {
        return $this->belongsTo(Habitat::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class)->withTrashed();
    }

    public function gameAsset(): BelongsTo
    {
        return $this->belongsTo(GameAsset::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(AssessmentScore::class);
    }

    public function growthHistories(): HasMany
    {
        return $this->hasMany(GrowthHistory::class);
    }

    public function studentAchievements(): HasMany
    {
        return $this->hasMany(StudentAchievement::class);
    }

    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'student_achievements')
            ->withPivot('awarded_at', 'assessment_score_id');
    }
}
