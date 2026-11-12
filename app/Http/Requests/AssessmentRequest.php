<?php

namespace App\Http\Requests;

use App\Enums\AssessmentType;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AssessmentRequest extends FormRequest
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
        return [
            'title' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::enum(AssessmentType::class)],
            'custom_type' => ['nullable', 'required_if:type,'.AssessmentType::Custom->value, 'string', 'max:60'],
            'assessed_on' => ['required', 'date'],
            'total_score' => ['required', 'numeric', 'min:1', 'max:10000'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validatedAttributes(): array
    {
        $data = $this->validated();

        if ($data['type'] !== AssessmentType::Custom->value) {
            $data['custom_type'] = null;
        }

        return $data;
    }
}
