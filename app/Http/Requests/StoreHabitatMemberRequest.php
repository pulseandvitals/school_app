<?php

namespace App\Http\Requests;

use App\Models\GameAsset;
use App\Models\Habitat;
use App\Models\Student;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Add a student to a habitat: either an existing student from the teacher's
 * roster (student_id) or a brand-new student (name, ...).
 */
class StoreHabitatMemberRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->habitat());
    }

    public function habitat(): Habitat
    {
        return $this->route('habitat');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $habitat = $this->habitat();
        $teacherId = $this->user()->id;

        return [
            'student_id' => [
                'nullable',
                'integer',
                Rule::exists(Student::class, 'id')->where('user_id', $teacherId)->whereNull('deleted_at'),
                Rule::unique('habitat_members', 'student_id')->where('habitat_id', $habitat->id)->whereNull('deleted_at'),
            ],
            'name' => ['required_without:student_id', 'nullable', 'string', 'max:120'],
            'student_number' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique(Student::class)->where('user_id', $teacherId),
            ],
            'photo' => ['nullable', 'image', 'max:2048'],
            'game_asset_id' => [
                'required',
                'integer',
                Rule::exists(GameAsset::class, 'id')->where(fn (Builder $query) => $query
                    ->where('type', $habitat->type->assetType()->value)
                    ->where('is_active', true)),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'student_id.unique' => 'This student is already in this '.strtolower($this->habitat()->type->config()['singular']).'.',
            'name.required_without' => 'Enter the student\'s name or pick an existing student.',
            'game_asset_id.required' => 'Choose a '.$this->habitat()->type->config()['member_noun'].' for this student.',
        ];
    }
}
