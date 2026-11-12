<?php

namespace App\Models;

use Database\Factories\StudentAchievementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['habitat_member_id', 'achievement_id', 'assessment_score_id', 'awarded_at'])]
#[WithoutTimestamps]
class StudentAchievement extends Model
{
    /** @use HasFactory<StudentAchievementFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'awarded_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(HabitatMember::class, 'habitat_member_id')->withTrashed();
    }

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }
}
