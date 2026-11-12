<?php

namespace App\Http\Requests;

use App\Enums\HabitatType;
use App\Http\Middleware\ResolveHabitatType;
use App\Models\GrowthRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create/update an aquarium or garden. Authorisation is handled by the
 * controller via HabitatPolicy.
 */
class HabitatRequest extends FormRequest
{
    public function habitatType(): HabitatType
    {
        return $this->attributes->get(ResolveHabitatType::ATTRIBUTE);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $type = $this->habitatType();

        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'theme' => ['required', 'string', Rule::in($type->themeKeys())],
            'growth_rule_id' => [
                'nullable',
                'integer',
                Rule::exists(GrowthRule::class, 'id')->where(fn (Builder $query) => $query
                    ->where('habitat_type', $type->value)
                    ->where(fn (Builder $query) => $query->whereNull('user_id')->orWhere('user_id', $this->user()->id))),
            ],
        ];
    }
}
