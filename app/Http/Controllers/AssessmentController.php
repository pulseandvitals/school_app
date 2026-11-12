<?php

namespace App\Http\Controllers;

use App\Enums\AssessmentType;
use App\Http\Requests\AssessmentRequest;
use App\Http\Resources\AssessmentResource;
use App\Http\Resources\HabitatMemberResource;
use App\Http\Resources\HabitatResource;
use App\Models\ActivityLog;
use App\Models\Assessment;
use App\Models\Habitat;
use App\Models\HabitatMember;
use App\Services\Growth\ScoreRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AssessmentController extends Controller
{
    public function index(Habitat $habitat): Response
    {
        Gate::authorize('view', $habitat);

        return Inertia::render('Assessments/Index', [
            'habitat' => HabitatResource::make($habitat->loadCount('members')),
            'assessments' => AssessmentResource::collection(
                $habitat->assessments()
                    ->withCount('scores')
                    ->withAvg('scores', 'percentage')
                    ->latest('assessed_on')
                    ->latest('id')
                    ->get(),
            ),
        ]);
    }

    public function create(Habitat $habitat): Response
    {
        Gate::authorize('update', $habitat);

        return Inertia::render('Assessments/Form', [
            'habitat' => HabitatResource::make($habitat),
            'assessment' => null,
            'types' => $this->types(),
        ]);
    }

    public function store(AssessmentRequest $request, Habitat $habitat): RedirectResponse
    {
        $assessment = $habitat->assessments()->create($request->validatedAttributes());
        ActivityLog::record($request->user(), 'assessment.created', $assessment, ['title' => $assessment->title]);

        return to_route($habitat->type->routePrefix().'.assessments.show', [$habitat, $assessment])
            ->with('success', 'Assessment created. Enter the scores below.');
    }

    /**
     * The score entry grid.
     */
    public function show(Habitat $habitat, Assessment $assessment): Response
    {
        Gate::authorize('view', $habitat);

        $members = $habitat->members()
            ->with(['student', 'gameAsset'])
            ->get()
            ->each(fn (HabitatMember $member) => $member->setRelation('habitat', $habitat))
            ->sortBy(fn (HabitatMember $member) => mb_strtolower($member->student->name))
            ->values();

        return Inertia::render('Assessments/Show', [
            'habitat' => HabitatResource::make($habitat),
            'assessment' => AssessmentResource::make($assessment),
            'members' => HabitatMemberResource::collection($members),
            'scores' => $assessment->scores()
                ->get(['habitat_member_id', 'score', 'percentage', 'growth_points_awarded'])
                ->mapWithKeys(fn ($score): array => [$score->habitat_member_id => [
                    'score' => (float) $score->score,
                    'percentage' => (float) $score->percentage,
                    'points' => $score->growth_points_awarded,
                ]]),
            'pointBands' => $habitat->growthCalculator()->pointBands(),
        ]);
    }

    public function edit(Habitat $habitat, Assessment $assessment): Response
    {
        Gate::authorize('update', $habitat);

        return Inertia::render('Assessments/Form', [
            'habitat' => HabitatResource::make($habitat),
            'assessment' => AssessmentResource::make($assessment),
            'types' => $this->types(),
        ]);
    }

    public function update(AssessmentRequest $request, Habitat $habitat, Assessment $assessment, ScoreRecorder $recorder): RedirectResponse
    {
        DB::transaction(function () use ($request, $assessment, $recorder): void {
            $assessment->fill($request->validatedAttributes());
            $totalChanged = $assessment->isDirty('total_score');
            $changes = $assessment->getDirty();
            $assessment->save();

            if ($totalChanged) {
                // Percentages depend on the total, so re-run every recorded score.
                $existing = $assessment->scores()->pluck('score', 'habitat_member_id')
                    ->map(fn ($score): float => min((float) $score, (float) $assessment->total_score))
                    ->all();
                $recorder->record($assessment, $existing, $request->user());
            }

            ActivityLog::record($request->user(), 'assessment.updated', $assessment, $changes);
        });

        return to_route($habitat->type->routePrefix().'.assessments.show', [$habitat, $assessment])
            ->with('success', 'Assessment updated.');
    }

    public function destroy(Request $request, Habitat $habitat, Assessment $assessment, ScoreRecorder $recorder): RedirectResponse
    {
        Gate::authorize('update', $habitat);

        DB::transaction(function () use ($request, $assessment, $recorder): void {
            // Remove scores through the recorder so growth is recalculated and logged.
            $memberIds = $assessment->scores()->pluck('habitat_member_id');
            $recorder->record($assessment, $memberIds->mapWithKeys(fn (int $id): array => [$id => null])->all(), $request->user());

            $assessment->delete();
            ActivityLog::record($request->user(), 'assessment.deleted', $assessment, ['title' => $assessment->title]);
        });

        return to_route($habitat->type->routePrefix().'.assessments.index', $habitat)
            ->with('success', 'Assessment deleted and growth recalculated.');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function types(): array
    {
        return array_map(
            fn (AssessmentType $type): array => ['value' => $type->value, 'label' => $type->label()],
            AssessmentType::cases(),
        );
    }
}
