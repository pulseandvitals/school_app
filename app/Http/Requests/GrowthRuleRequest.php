<?php

namespace App\Http\Requests;

use App\Enums\HabitatType;
use App\Models\GrowthRule;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GrowthRuleRequest extends FormRequest
{
    public function authorize(): Response
    {
        $rule = $this->route('growth_rule');

        return $rule ? Gate::inspect('update', $rule) : Gate::inspect('create', GrowthRule::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            // The type is fixed once a rule exists.
            'habitat_type' => $this->route('growth_rule')
                ? ['prohibited']
                : ['required', Rule::enum(HabitatType::class)],
            'point_bands' => ['required', 'array', 'min:1', 'max:20'],
            'point_bands.*.min' => ['required', 'numeric', 'min:0', 'max:100', 'distinct'],
            'point_bands.*.points' => ['required', 'integer', 'min:0', 'max:1000'],
            'stages' => ['required', 'array', 'min:2', 'max:10'],
            'stages.*.name' => ['required', 'string', 'max:40'],
            'stages.*.min_points' => ['required', 'integer', 'min:0', 'max:100000', 'distinct'],
            'stages.*.scale' => ['required', 'numeric', 'min:0.2', 'max:2'],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if (! collect($this->input('point_bands'))->contains(fn (array $band): bool => (float) $band['min'] === 0.0)) {
                    $validator->errors()->add('point_bands', 'Add a band starting at 0% so every score is covered.');
                }

                if (! collect($this->input('stages'))->contains(fn (array $stage): bool => (int) $stage['min_points'] === 0)) {
                    $validator->errors()->add('stages', 'The first stage must start at 0 points.');
                }
            },
        ];
    }

    /**
     * Normalised bands and stages, ordered and numbered.
     *
     * @return array{name: string, point_bands: array<int, array{min: float, points: int}>, stages: array<int, array{stage: int, name: string, min_points: int, scale: float}>}
     */
    public function ruleAttributes(): array
    {
        $bands = collect($this->validated('point_bands'))
            ->map(fn (array $band): array => ['min' => (float) $band['min'], 'points' => (int) $band['points']])
            ->sortByDesc('min')
            ->values()
            ->all();

        $stages = collect($this->validated('stages'))
            ->sortBy(fn (array $stage): int => (int) $stage['min_points'])
            ->values()
            ->map(fn (array $stage, int $index): array => [
                'stage' => $index + 1,
                'name' => $stage['name'],
                'min_points' => (int) $stage['min_points'],
                'scale' => round((float) $stage['scale'], 2),
            ])
            ->all();

        return [
            'name' => $this->validated('name'),
            'point_bands' => $bands,
            'stages' => $stages,
        ];
    }
}
