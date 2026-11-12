<?php

namespace App\Http\Controllers;

use App\Enums\HabitatType;
use App\Http\Middleware\ResolveHabitatType;
use App\Http\Requests\HabitatRequest;
use App\Http\Resources\AssessmentResource;
use App\Http\Resources\HabitatMemberResource;
use App\Http\Resources\HabitatResource;
use App\Models\ActivityLog;
use App\Models\GrowthRule;
use App\Models\Habitat;
use App\Models\HabitatMember;
use App\Services\Growth\GrowthRecalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Aquariums and gardens share this controller; the route group decides the type.
 */
class HabitatController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Habitat::class);
        $type = $this->type($request);

        $habitats = $request->user()->habitats()
            ->ofType($type)
            ->withCount(['members', 'assessments'])
            ->withAvg('members', 'growth_stage')
            ->latest()
            ->get();

        return Inertia::render('Habitats/Index', [
            'habitatType' => $type->value,
            'habitats' => HabitatResource::collection($habitats->whereNull('archived_at')->values()),
            'archived' => HabitatResource::collection($habitats->whereNotNull('archived_at')->values()),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Habitat::class);

        return Inertia::render('Habitats/Form', [
            'habitatType' => $this->type($request)->value,
            'habitat' => null,
            'growthRules' => $this->growthRuleOptions($request, $this->type($request)),
        ]);
    }

    public function store(HabitatRequest $request): RedirectResponse
    {
        Gate::authorize('create', Habitat::class);
        $type = $request->habitatType();

        $habitat = new Habitat($request->validated());
        $habitat->type = $type;
        $habitat->user()->associate($request->user());
        $habitat->save();

        ActivityLog::record($request->user(), 'habitat.created', $habitat, ['name' => $habitat->name]);

        return to_route($type->routePrefix().'.show', $habitat)
            ->with('success', $type->config()['singular'].' created! Now add your students.');
    }

    public function show(Request $request, Habitat $habitat): Response
    {
        Gate::authorize('view', $habitat);
        $habitat->load('growthRule');

        $members = $habitat->members()
            ->with(['student', 'gameAsset'])
            ->withCount(['scores', 'studentAchievements'])
            ->withAvg('scores', 'percentage')
            ->get()
            ->each(fn (HabitatMember $member) => $member->setRelation('habitat', $habitat))
            ->sortBy(fn (HabitatMember $member) => mb_strtolower($member->student->name))
            ->values();

        $recentAssessments = $habitat->assessments()
            ->withCount('scores')
            ->withAvg('scores', 'percentage')
            ->latest('assessed_on')
            ->latest('id')
            ->limit(5)
            ->get();

        return Inertia::render('Habitats/Show', [
            'habitat' => HabitatResource::make($habitat),
            'members' => HabitatMemberResource::collection($members),
            'recentAssessments' => AssessmentResource::collection($recentAssessments),
            'growthRule' => [
                'name' => $habitat->effectiveGrowthRule()->name,
                'stages' => $habitat->growthCalculator()->stages(),
                'point_bands' => $habitat->growthCalculator()->pointBands(),
            ],
        ]);
    }

    public function edit(Request $request, Habitat $habitat): Response
    {
        Gate::authorize('update', $habitat);

        return Inertia::render('Habitats/Form', [
            'habitatType' => $habitat->type->value,
            'habitat' => HabitatResource::make($habitat),
            'growthRules' => $this->growthRuleOptions($request, $habitat->type),
        ]);
    }

    public function update(HabitatRequest $request, Habitat $habitat, GrowthRecalculator $recalculator): RedirectResponse
    {
        Gate::authorize('update', $habitat);

        $habitat->fill($request->validated());
        $ruleChanged = $habitat->isDirty('growth_rule_id');
        $changes = $habitat->getDirty();
        $habitat->save();

        if ($ruleChanged) {
            $recalculator->reapplyRule($habitat);
        }

        ActivityLog::record($request->user(), 'habitat.updated', $habitat, $changes);

        return to_route($habitat->type->routePrefix().'.show', $habitat)
            ->with('success', $ruleChanged ? 'Saved. Growth was recalculated with the new rules.' : 'Changes saved.');
    }

    /**
     * Toggle the archived state.
     */
    public function archive(Request $request, Habitat $habitat): RedirectResponse
    {
        Gate::authorize('update', $habitat);

        $habitat->forceFill(['archived_at' => $habitat->isArchived() ? null : now()])->save();
        ActivityLog::record($request->user(), $habitat->isArchived() ? 'habitat.archived' : 'habitat.restored', $habitat);

        $label = $habitat->type->config()['singular'];

        return to_route($habitat->type->routePrefix().'.index')
            ->with('success', $habitat->isArchived() ? "{$label} archived." : "{$label} restored.");
    }

    public function destroy(Request $request, Habitat $habitat): RedirectResponse
    {
        Gate::authorize('delete', $habitat);

        $habitat->delete();
        ActivityLog::record($request->user(), 'habitat.deleted', $habitat, ['name' => $habitat->name]);

        return to_route($habitat->type->routePrefix().'.index')
            ->with('success', $habitat->type->config()['singular'].' deleted.');
    }

    private function type(Request $request): HabitatType
    {
        return $request->attributes->get(ResolveHabitatType::ATTRIBUTE);
    }

    /**
     * @return array<int, array{id: int|null, name: string, is_default: bool}>
     */
    private function growthRuleOptions(Request $request, HabitatType $type): array
    {
        $default = GrowthRule::defaultFor($type);

        return GrowthRule::query()
            ->availableTo($request->user())
            ->where('habitat_type', $type)
            ->whereKeyNot($default->id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (GrowthRule $rule): array => ['id' => $rule->id, 'name' => $rule->name, 'is_default' => false])
            ->prepend(['id' => null, 'name' => $default->name.' (default)', 'is_default' => true])
            ->all();
    }
}
