<?php

namespace App\Http\Resources;

use App\Models\Assessment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Assessment
 */
class AssessmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type->value,
            'type_label' => $this->type_label,
            'custom_type' => $this->custom_type,
            'assessed_on' => $this->assessed_on->toDateString(),
            'total_score' => (float) $this->total_score,
            'description' => $this->description,
            'scores_count' => $this->whenCounted('scores'),
            'average_percentage' => $this->when(
                array_key_exists('scores_avg_percentage', $this->resource->getAttributes()),
                fn () => $this->scores_avg_percentage === null ? null : round((float) $this->scores_avg_percentage, 1),
            ),
        ];
    }
}
