<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecordScoresRequest;
use App\Models\Assessment;
use App\Models\GrowthHistory;
use App\Models\Habitat;
use App\Services\Growth\ScoreRecorder;
use Illuminate\Http\RedirectResponse;

class AssessmentScoreController extends Controller
{
    public function update(RecordScoresRequest $request, Habitat $habitat, Assessment $assessment, ScoreRecorder $recorder): RedirectResponse
    {
        $summary = $recorder->record($assessment, $request->scores(), $request->user());
        $calculator = $habitat->growthCalculator();

        $celebrations = collect($summary['stage_ups'])
            ->map(fn (GrowthHistory $history): array => [
                'name' => $history->member->student->name,
                'stage' => $history->stage_after,
                'stage_name' => collect($calculator->stages())->firstWhere('stage', $history->stage_after)['name'] ?? 'Stage '.$history->stage_after,
            ])
            ->all();

        $saved = $summary['recorded'] + $summary['updated'] + $summary['removed'];

        return back()
            ->with('success', $saved === 0 ? 'No changes to save.' : "Saved {$saved} ".str('score')->plural($saved).'.')
            ->with('celebrations', $celebrations);
    }
}
