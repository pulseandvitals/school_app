<?php

namespace App\Models;

use Database\Factories\AssessmentScoreFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['assessment_id', 'habitat_member_id', 'score', 'percentage', 'growth_points_awarded', 'recorded_by'])]
class AssessmentScore extends Model
{
    /** @use HasFactory<AssessmentScoreFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'percentage' => 'decimal:2',
            'growth_points_awarded' => 'integer',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(HabitatMember::class, 'habitat_member_id')->withTrashed();
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
