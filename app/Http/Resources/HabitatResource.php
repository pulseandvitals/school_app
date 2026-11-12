<?php

namespace App\Http\Resources;

use App\Models\Habitat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Habitat
 */
class HabitatResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'route' => $this->type->routePrefix(),
            'name' => $this->name,
            'description' => $this->description,
            'theme' => $this->theme,
            'theme_label' => $this->type->config()['themes'][$this->theme] ?? $this->theme,
            'growth_rule_id' => $this->growth_rule_id,
            'is_archived' => $this->isArchived(),
            'archived_at' => $this->archived_at?->toDateString(),
            'members_count' => $this->whenCounted('members'),
            'assessments_count' => $this->whenCounted('assessments'),
            'average_stage' => $this->when(isset($this->members_avg_growth_stage), fn () => round((float) $this->members_avg_growth_stage, 1)),
            'created_at' => $this->created_at?->toDateString(),
        ];
    }
}
