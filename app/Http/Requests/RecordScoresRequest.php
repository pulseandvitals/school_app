<?php

namespace App\Http\Requests;

use App\Models\Assessment;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class RecordScoresRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->route('habitat'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Assessment $assessment */
        $assessment = $this->route('assessment');

        return [
            'scores' => ['required', 'array'],
            'scores.*' => ['nullable', 'numeric', 'min:0', 'max:'.(float) $assessment->total_score],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'scores.*.max' => 'A score cannot be higher than the total of :max.',
            'scores.*.min' => 'Scores cannot be negative.',
            'scores.*.numeric' => 'Scores must be numbers.',
        ];
    }

    /**
     * Raw scores keyed by habitat member id.
     *
     * @return array<int, string|null>
     */
    public function scores(): array
    {
        $scores = [];

        foreach ($this->validated('scores') as $memberId => $score) {
            if (ctype_digit((string) $memberId)) {
                $scores[(int) $memberId] = $score;
            }
        }

        return $scores;
    }
}
