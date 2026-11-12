<?php

namespace App\Http\Controllers;

use App\Enums\HabitatType;
use App\Http\Requests\GrowthRuleRequest;
use App\Models\ActivityLog;
use App\Models\GrowthRule;
use App\Models\Habitat;
use App\Services\Growth\GrowthRecalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class GrowthRuleController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', GrowthRule::class);

        foreach (HabitatType::cases() as $type) {
            GrowthRule::defaultFor($type);
        }

        $rules = GrowthRule::query()
            ->availableTo($request->user())
            ->withCount(['habitats' => fn ($query) => $query->whereBelongsTo($request->user())])
            ->orderByRaw('user_id is not null')
            ->orderBy('name')
            ->get()
            ->map(fn (GrowthRule $rule): array => $this->present($rule));

        return Inertia::render('GrowthRules/Index', [
            'rules' => $rules,
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', GrowthRule::class);

        $validated = $request->validate(['type' => ['nullable', Rule::enum(HabitatType::class)]]);
        $type = HabitatType::tryFrom($validated['type'] ?? '') ?? HabitatType::Aquarium;
        $default = GrowthRule::defaultFor($type);

        return Inertia::render('GrowthRules/Form', [
            'rule' => null,
            'template' => [
                'name' => 'My '.strtolower($default->name),
                'habitat_type' => $type->value,
                'point_bands' => $default->point_bands,
                'stages' => $default->stages,
            ],
        ]);
    }

    public function store(GrowthRuleRequest $request): RedirectResponse
    {
        Gate::authorize('create', GrowthRule::class);

        $rule = new GrowthRule($request->ruleAttributes());
        $rule->habitat_type = HabitatType::from($request->validated('habitat_type'));
        $rule->user()->associate($request->user());
        $rule->save();

        ActivityLog::record($request->user(), 'growth_rule.created', $rule, ['name' => $rule->name]);

        return to_route('growth-rules.index')
            ->with('success', 'Growth rule created. Choose it in an '.strtolower($rule->habitat_type->config()['singular']).'\'s settings to use it.');
    }

    public function edit(GrowthRule $growthRule): Response
    {
        Gate::authorize('update', $growthRule);

        return Inertia::render('GrowthRules/Form', [
            'rule' => $this->present($growthRule),
            'template' => null,
        ]);
    }

    public function update(GrowthRuleRequest $request, GrowthRule $growthRule, GrowthRecalculator $recalculator): RedirectResponse
    {
        Gate::authorize('update', $growthRule);

        DB::transaction(function () use ($request, $growthRule, $recalculator): void {
            $growthRule->update($request->ruleAttributes());
            $growthRule->habitats()->get()->each(fn (Habitat $habitat) => $recalculator->reapplyRule($habitat));
            ActivityLog::record($request->user(), 'growth_rule.updated', $growthRule, $growthRule->getChanges());
        });

        return to_route('growth-rules.index')->with('success', 'Growth rule saved and growth recalculated.');
    }

    public function destroy(Request $request, GrowthRule $growthRule, GrowthRecalculator $recalculator): RedirectResponse
    {
        Gate::authorize('delete', $growthRule);

        DB::transaction(function () use ($request, $growthRule, $recalculator): void {
            $habitats = $growthRule->habitats()->get();
            $growthRule->habitats()->update(['growth_rule_id' => null]);
            $growthRule->delete();
            $habitats->each(fn (Habitat $habitat) => $recalculator->reapplyRule($habitat->setAttribute('growth_rule_id', null)));
            ActivityLog::record($request->user(), 'growth_rule.deleted', $growthRule, ['name' => $growthRule->name]);
        });

        return to_route('growth-rules.index')->with('success', 'Growth rule deleted. Habitats using it now follow the default rules.');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(GrowthRule $rule): array
    {
        return [
            'id' => $rule->id,
            'name' => $rule->name,
            'habitat_type' => $rule->habitat_type->value,
            'is_default' => $rule->isSystemDefault(),
            'point_bands' => $rule->calculator()->pointBands(),
            'stages' => $rule->calculator()->stages(),
            'habitats_count' => $rule->habitats_count ?? null,
        ];
    }
}
