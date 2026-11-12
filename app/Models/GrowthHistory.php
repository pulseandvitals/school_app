<?php

namespace App\Models;

use App\Enums\GrowthChangeReason;
use Database\Factories\GrowthHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only ledger explaining every change to a member's growth.
 */
#[Fillable(['habitat_member_id', 'assessment_score_id', 'reason', 'description', 'points_before', 'points_after', 'delta', 'stage_before', 'stage_after'])]
class GrowthHistory extends Model
{
    /** @use HasFactory<GrowthHistoryFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'reason' => GrowthChangeReason::class,
            'points_before' => 'integer',
            'points_after' => 'integer',
            'delta' => 'integer',
            'stage_before' => 'integer',
            'stage_after' => 'integer',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(HabitatMember::class, 'habitat_member_id')->withTrashed();
    }

    public function assessmentScore(): BelongsTo
    {
        return $this->belongsTo(AssessmentScore::class);
    }

    public function isStageUp(): bool
    {
        return $this->stage_after > $this->stage_before;
    }
}
