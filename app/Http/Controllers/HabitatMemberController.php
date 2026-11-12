<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHabitatMemberRequest;
use App\Http\Requests\UpdateHabitatMemberRequest;
use App\Http\Resources\HabitatMemberResource;
use App\Http\Resources\HabitatResource;
use App\Models\Achievement;
use App\Models\ActivityLog;
use App\Models\AssessmentScore;
use App\Models\GameAsset;
use App\Models\GrowthHistory;
use App\Models\Habitat;
use App\Models\HabitatMember;
use App\Models\Student;
use App\Services\Students\StudentProfileUpdater;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class HabitatMemberController extends Controller
{
    public function create(Request $request, Habitat $habitat): Response
    {
        Gate::authorize('update', $habitat);

        $enrolledStudentIds = $habitat->members()->pluck('student_id');

        return Inertia::render('Members/Form', [
            'habitat' => HabitatResource::make($habitat),
            'member' => null,
            'assets' => $this->assets($habitat),
            'rosterStudents' => $request->user()->students()
                ->whereNotIn('id', $enrolledStudentIds)
                ->orderBy('name')
                ->get(['id', 'name', 'student_number', 'photo_path']),
        ]);
    }

    public function store(StoreHabitatMemberRequest $request, Habitat $habitat, StudentProfileUpdater $profiles): RedirectResponse
    {
        $member = DB::transaction(function () use ($request, $habitat, $profiles): HabitatMember {
            $student = $request->filled('student_id')
                ? $request->user()->students()->findOrFail($request->integer('student_id'))
                : $profiles->save(
                    $request->user()->students()->make(),
                    $request->safe()->only(['name', 'student_number']),
                    $request->file('photo'),
                );

            // Re-adding a previously removed student restores their history.
            $member = $habitat->members()->withTrashed()->firstOrNew(['student_id' => $student->id]);
            $member->game_asset_id = $request->integer('game_asset_id');
            $member->deleted_at = null;
            $member->save();

            return $member;
        });

        ActivityLog::record($request->user(), 'member.added', $member, ['student_id' => $member->student_id]);

        return to_route($habitat->type->routePrefix().'.show', $habitat)
            ->with('success', "{$member->student->name} joined the {$habitat->name}!");
    }

    /**
     * Individual student report for this habitat.
     */
    public function show(Request $request, Habitat $habitat, HabitatMember $member): Response
    {
        Gate::authorize('view', $habitat);

        $member->load(['student', 'gameAsset'])->setRelation('habitat', $habitat);

        $scores = $member->scores()
            ->with('assessment')
            ->get()
            ->sortByDesc(fn (AssessmentScore $score) => $score->assessment->assessed_on->toDateString().'-'.str_pad((string) $score->assessment_id, 10, '0', STR_PAD_LEFT))
            ->values();

        $earned = $member->studentAchievements()->get()->keyBy('achievement_id');

        return Inertia::render('Members/Show', [
            'habitat' => HabitatResource::make($habitat),
            'member' => HabitatMemberResource::make($member),
            'stats' => $this->stats($scores),
            'scores' => $scores->map(fn (AssessmentScore $score): array => [
                'id' => $score->id,
                'assessment_id' => $score->assessment_id,
                'title' => $score->assessment->title,
                'type_label' => $score->assessment->type_label,
                'assessed_on' => $score->assessment->assessed_on->toDateString(),
                'score' => (float) $score->score,
                'total_score' => (float) $score->assessment->total_score,
                'percentage' => (float) $score->percentage,
                'points' => $score->growth_points_awarded,
            ]),
            'timeline' => $member->growthHistories()
                ->latest('id')
                ->limit(100)
                ->get()
                ->map(fn (GrowthHistory $entry): array => [
                    'id' => $entry->id,
                    'reason' => $entry->reason->value,
                    'description' => $entry->description,
                    'delta' => $entry->delta,
                    'points_after' => $entry->points_after,
                    'stage_before' => $entry->stage_before,
                    'stage_after' => $entry->stage_after,
                    'created_at' => $entry->created_at?->toIso8601String(),
                ]),
            'achievements' => Achievement::query()
                ->applicableTo($habitat->type)
                ->orderBy('id')
                ->get()
                ->map(fn (Achievement $achievement): array => [
                    'id' => $achievement->id,
                    'name' => $achievement->name,
                    'description' => $achievement->description,
                    'icon' => $achievement->icon,
                    'awarded_at' => $earned->get($achievement->id)?->awarded_at?->toDateString(),
                ]),
            'otherHabitats' => $member->student->memberships()
                ->whereKeyNot($member->id)
                ->with('habitat')
                ->get()
                ->filter(fn (HabitatMember $other) => $other->habitat !== null)
                ->map(fn (HabitatMember $other): array => [
                    'member_id' => $other->id,
                    'habitat_id' => $other->habitat_id,
                    'name' => $other->habitat->name,
                    'route' => $other->habitat->type->routePrefix(),
                    'type' => $other->habitat->type->value,
                ])
                ->values(),
        ]);
    }

    public function edit(Request $request, Habitat $habitat, HabitatMember $member): Response
    {
        Gate::authorize('update', $habitat);

        $member->load(['student', 'gameAsset'])->setRelation('habitat', $habitat);

        return Inertia::render('Members/Form', [
            'habitat' => HabitatResource::make($habitat),
            'member' => HabitatMemberResource::make($member),
            'assets' => $this->assets($habitat, $member->game_asset_id),
            'rosterStudents' => [],
        ]);
    }

    public function update(UpdateHabitatMemberRequest $request, Habitat $habitat, HabitatMember $member, StudentProfileUpdater $profiles): RedirectResponse
    {
        DB::transaction(function () use ($request, $member, $profiles): void {
            /** @var Student $student */
            $student = $member->student;
            $profiles->save(
                $student,
                $request->safe()->only(['name', 'student_number']),
                $request->file('photo'),
                $request->boolean('remove_photo'),
            );

            $previousAsset = $member->game_asset_id;
            $member->game_asset_id = $request->integer('game_asset_id');
            $member->save();

            if ($previousAsset !== $member->game_asset_id) {
                ActivityLog::record($request->user(), 'member.asset_changed', $member, [
                    'from' => $previousAsset,
                    'to' => $member->game_asset_id,
                ]);
            }
        });

        return to_route($habitat->type->routePrefix().'.members.show', [$habitat, $member])
            ->with('success', 'Student updated.');
    }

    public function destroy(Request $request, Habitat $habitat, HabitatMember $member): RedirectResponse
    {
        Gate::authorize('update', $habitat);

        $member->delete();
        ActivityLog::record($request->user(), 'member.removed', $member, ['student_id' => $member->student_id]);

        return to_route($habitat->type->routePrefix().'.show', $habitat)
            ->with('success', "{$member->student->name} was removed. Their scores are kept if you add them back.");
    }

    /**
     * Selectable assets for this habitat; an inactive asset already in use stays visible.
     *
     * @return Collection<int, GameAsset>
     */
    private function assets(Habitat $habitat, ?int $currentAssetId = null): Collection
    {
        return GameAsset::query()
            ->ofType($habitat->type->assetType())
            ->where(fn ($query) => $query->where('is_active', true)->when($currentAssetId, fn ($query) => $query->orWhere('id', $currentAssetId)))
            ->orderBy('asset_number')
            ->get(['id', 'type', 'name', 'image_path', 'asset_number', 'stage_images', 'is_active']);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, AssessmentScore>  $scores
     * @return array{completed: int, average: float|null, highest: float|null, lowest: float|null}
     */
    private function stats(\Illuminate\Support\Collection $scores): array
    {
        $percentages = $scores->map(fn (AssessmentScore $score): float => (float) $score->percentage);

        return [
            'completed' => $percentages->count(),
            'average' => $percentages->isEmpty() ? null : round($percentages->avg(), 1),
            'highest' => $percentages->max(),
            'lowest' => $percentages->min(),
        ];
    }
}
