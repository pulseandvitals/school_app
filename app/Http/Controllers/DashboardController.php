<?php

namespace App\Http\Controllers;

use App\Enums\HabitatType;
use App\Enums\PerformanceLevel;
use App\Http\Resources\HabitatResource;
use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\GrowthHistory;
use App\Models\Habitat;
use App\Models\HabitatMember;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const LIST_LIMIT = 5;

    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $habitats = $user->habitats()
            ->active()
            ->with('growthRule')
            ->withCount(['members', 'assessments'])
            ->withAvg('members', 'growth_stage')
            ->latest()
            ->get();

        $habitatIds = $habitats->modelKeys();

        $sections = [];
        foreach (HabitatType::cases() as $type) {
            $sections[$type->value] = $this->section($habitats->where('type', $type)->values());
        }

        return Inertia::render('Dashboard', [
            'totals' => [
                'students' => $user->students()->count(),
                'aquariums' => $habitats->where('type', HabitatType::Aquarium)->count(),
                'gardens' => $habitats->where('type', HabitatType::Garden)->count(),
                'assessments' => $habitats->sum('assessments_count'),
                'average' => $this->averagePercentage($habitatIds),
            ],
            'sections' => $sections,
            'recentAssessments' => $this->recentAssessments($habitatIds, $habitats),
            'milestones' => $this->milestones($habitatIds, $habitats),
        ]);
    }

    /**
     * @param  Collection<int, Habitat>  $habitats
     * @return array<string, mixed>
     */
    private function section(Collection $habitats): array
    {
        $ids = $habitats->modelKeys();
        $byId = $habitats->keyBy('id');

        $topGrowers = HabitatMember::query()
            ->whereIn('habitat_id', $ids)
            ->with(['student', 'gameAsset'])
            ->orderByDesc('growth_points')
            ->limit(self::LIST_LIMIT)
            ->get();

        $needsImprovement = HabitatMember::query()
            ->whereIn('habitat_id', $ids)
            ->with(['student', 'gameAsset'])
            ->withAvg('scores', 'percentage')
            ->whereHas('scores')
            ->orderBy('scores_avg_percentage')
            ->limit(self::LIST_LIMIT * 2)
            ->get()
            ->filter(fn (HabitatMember $member): bool => PerformanceLevel::fromAverage((float) $member->scores_avg_percentage) === PerformanceLevel::NeedsImprovement)
            ->take(self::LIST_LIMIT)
            ->values();

        return [
            'habitats' => HabitatResource::collection($habitats->take(6)),
            'students' => $habitats->sum('members_count'),
            'average' => $this->averagePercentage($ids),
            'topGrowers' => $topGrowers->map(fn (HabitatMember $member): array => $this->memberSummary($member, $byId->get($member->habitat_id))),
            'needsImprovement' => $needsImprovement->map(fn (HabitatMember $member): array => $this->memberSummary($member, $byId->get($member->habitat_id))),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function memberSummary(HabitatMember $member, Habitat $habitat): array
    {
        return [
            'id' => $member->id,
            'habitat_id' => $habitat->id,
            'habitat_name' => $habitat->name,
            'route' => $habitat->type->routePrefix(),
            'name' => $member->student->name,
            'image_url' => $member->gameAsset->image_url,
            'points' => $member->growth_points,
            'stage' => $member->growth_stage,
            'stage_name' => $habitat->growthCalculator()->stageDefinitionForPoints($member->growth_points)['name'],
            'average' => isset($member->scores_avg_percentage) ? round((float) $member->scores_avg_percentage, 1) : null,
        ];
    }

    /**
     * @param  array<int, int>  $habitatIds
     */
    private function averagePercentage(array $habitatIds): ?float
    {
        $average = AssessmentScore::query()
            ->whereIn('assessment_id', Assessment::query()->select('id')->whereIn('habitat_id', $habitatIds))
            ->avg('percentage');

        return $average === null ? null : round((float) $average, 1);
    }

    /**
     * @param  array<int, int>  $habitatIds
     * @param  Collection<int, Habitat>  $habitats
     * @return array<int, array<string, mixed>>
     */
    private function recentAssessments(array $habitatIds, Collection $habitats): array
    {
        $byId = $habitats->keyBy('id');

        return Assessment::query()
            ->whereIn('habitat_id', $habitatIds)
            ->withCount('scores')
            ->withAvg('scores', 'percentage')
            ->latest('assessed_on')
            ->latest('id')
            ->limit(6)
            ->get()
            ->map(fn (Assessment $assessment): array => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'type_label' => $assessment->type_label,
                'assessed_on' => $assessment->assessed_on->toDateString(),
                'scores_count' => $assessment->scores_count,
                'average' => $assessment->scores_avg_percentage === null ? null : round((float) $assessment->scores_avg_percentage, 1),
                'habitat_id' => $assessment->habitat_id,
                'habitat_name' => $byId->get($assessment->habitat_id)->name,
                'habitat_type' => $byId->get($assessment->habitat_id)->type->value,
                'route' => $byId->get($assessment->habitat_id)->type->routePrefix(),
            ])
            ->all();
    }

    /**
     * Recent stage-ups across all habitats.
     *
     * @param  array<int, int>  $habitatIds
     * @param  Collection<int, Habitat>  $habitats
     * @return array<int, array<string, mixed>>
     */
    private function milestones(array $habitatIds, Collection $habitats): array
    {
        $byId = $habitats->keyBy('id');

        return GrowthHistory::query()
            ->whereColumn('stage_after', '>', 'stage_before')
            ->whereHas('member', fn (Builder $query) => $query->whereIn('habitat_id', $habitatIds))
            ->with('member.student', 'member.gameAsset')
            ->latest('id')
            ->limit(6)
            ->get()
            ->map(function (GrowthHistory $entry) use ($byId): array {
                $habitat = $byId->get($entry->member->habitat_id);

                return [
                    'id' => $entry->id,
                    'member_id' => $entry->habitat_member_id,
                    'name' => $entry->member->student->name,
                    'image_url' => $entry->member->gameAsset->image_url,
                    'habitat_id' => $habitat->id,
                    'habitat_name' => $habitat->name,
                    'habitat_type' => $habitat->type->value,
                    'route' => $habitat->type->routePrefix(),
                    'stage' => $entry->stage_after,
                    'stage_name' => collect($habitat->growthCalculator()->stages())->firstWhere('stage', $entry->stage_after)['name'] ?? 'Stage '.$entry->stage_after,
                    'created_at' => $entry->created_at?->toIso8601String(),
                ];
            })
            ->all();
    }
}
