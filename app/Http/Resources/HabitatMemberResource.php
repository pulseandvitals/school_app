<?php

namespace App\Http\Resources;

use App\Models\HabitatMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A student's fish or plant, with everything a scene needs to draw it.
 * Expects the habitat relation to be set (for its growth calculator).
 *
 * @mixin HabitatMember
 */
class HabitatMemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $calculator = $this->habitat->growthCalculator();
        $stage = $calculator->stageDefinitionForPoints($this->growth_points);

        return [
            'id' => $this->id,
            'student' => [
                'id' => $this->student->id,
                'name' => $this->student->name,
                'student_number' => $this->student->student_number,
                'photo_url' => $this->student->photo_url,
            ],
            'asset' => [
                'id' => $this->gameAsset->id,
                'name' => $this->gameAsset->name,
                'asset_number' => $this->gameAsset->asset_number,
                'image_url' => $this->gameAsset->image_url,
                'stage_image_urls' => $this->gameAsset->stage_image_urls,
                'is_active' => $this->gameAsset->is_active,
            ],
            'growth_points' => $this->growth_points,
            'growth' => [
                'stage' => $this->growth_stage,
                'name' => $stage['name'],
                'scale' => (float) $stage['scale'],
                'max_stage' => $calculator->maxStage(),
                'progress' => $calculator->stageProgress($this->growth_points),
                'points_to_next' => $calculator->pointsToNextStage($this->growth_points),
            ],
            'scores_count' => $this->whenCounted('scores'),
            'average_percentage' => $this->when(
                array_key_exists('scores_avg_percentage', $this->resource->getAttributes()),
                fn () => $this->scores_avg_percentage === null ? null : round((float) $this->scores_avg_percentage, 1),
            ),
            'achievements_count' => $this->whenCounted('studentAchievements'),
        ];
    }
}
