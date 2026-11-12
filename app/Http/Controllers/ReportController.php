<?php

namespace App\Http\Controllers;

use App\Enums\PerformanceLevel;
use App\Http\Resources\HabitatResource;
use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\Habitat;
use App\Models\HabitatMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class performance report for one aquarium or garden.
 */
class ReportController extends Controller
{
    public function __invoke(Request $request, Habitat $habitat): Response
    {
        Gate::authorize('view', $habitat);
        $calculator = $habitat->growthCalculator();

        $filters = $request->validate([
            'member' => ['nullable', 'integer'],
            'assessment' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'performance' => ['nullable', Rule::enum(PerformanceLevel::class)],
            'stage' => ['nullable', 'integer', 'min:1'],
        ]);

        $assessments = $habitat->assessments()
            ->when($filters['assessment'] ?? null, fn ($query, $id) => $query->whereKey($id))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('assessed_on', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('assessed_on', '<=', $to))
            ->orderBy('assessed_on')
            ->orderBy('id')
            ->get();

        $members = $habitat->members()
            ->with(['student', 'gameAsset'])
            ->when($filters['member'] ?? null, fn ($query, $id) => $query->whereKey($id))
            ->when($filters['stage'] ?? null, fn ($query, $stage) => $query->where('growth_stage', $stage))
            ->get();

        $scores = AssessmentScore::query()
            ->whereIn('assessment_id', $assessments->modelKeys())
            ->whereIn('habitat_member_id', $members->modelKeys())
            ->get(['assessment_id', 'habitat_member_id', 'percentage'])
            ->groupBy('habitat_member_id');

        $rows = $members
            ->map(function (HabitatMember $member) use ($scores, $calculator): array {
                $percentages = ($scores->get($member->id) ?? collect())
                    ->mapWithKeys(fn (AssessmentScore $score): array => [$score->assessment_id => (float) $score->percentage]);
                $average = $percentages->isEmpty() ? null : round($percentages->avg(), 1);

                return [
                    'id' => $member->id,
                    'name' => $member->student->name,
                    'image_url' => $member->gameAsset->image_url,
                    'stage' => $member->growth_stage,
                    'stage_name' => $calculator->stageDefinitionForPoints($member->growth_points)['name'],
                    'points' => $member->growth_points,
                    'completed' => $percentages->count(),
                    'average' => $average,
                    'highest' => $percentages->max(),
                    'lowest' => $percentages->min(),
                    'performance' => PerformanceLevel::fromAverage($average)?->value,
                    'by_assessment' => $percentages,
                ];
            })
            ->when($filters['performance'] ?? null, fn ($rows, $level) => $rows->where('performance', $level))
            ->sortBy(fn (array $row) => mb_strtolower($row['name']))
            ->values();

        $averages = $rows->pluck('average')->filter(fn ($average) => $average !== null);

        return Inertia::render('Reports/Class', [
            'habitat' => HabitatResource::make($habitat),
            'filters' => (object) $filters,
            'rows' => $rows,
            'assessments' => $assessments->map(fn (Assessment $assessment): array => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'assessed_on' => $assessment->assessed_on->toDateString(),
                'type_label' => $assessment->type_label,
            ]),
            'summary' => [
                'students' => $rows->count(),
                'class_average' => $averages->isEmpty() ? null : round($averages->avg(), 1),
                'assessments' => $assessments->count(),
                'needs_improvement' => $rows->where('performance', PerformanceLevel::NeedsImprovement->value)->count(),
            ],
            'options' => [
                'members' => $habitat->members()->with('student')->get()
                    ->map(fn (HabitatMember $member): array => ['value' => $member->id, 'label' => $member->student->name])
                    ->sortBy('label')
                    ->values(),
                'assessments' => $habitat->assessments()->latest('assessed_on')->get(['id', 'title'])
                    ->map(fn (Assessment $assessment): array => ['value' => $assessment->id, 'label' => $assessment->title]),
                'performance' => PerformanceLevel::options(),
                'stages' => collect($calculator->stages())
                    ->map(fn (array $stage): array => ['value' => $stage['stage'], 'label' => $stage['stage'].' · '.$stage['name']]),
            ],
        ]);
    }
}
