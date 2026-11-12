<?php

namespace App\Http\Requests;

use App\Models\GameAsset;
use App\Models\HabitatMember;
use App\Models\Student;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Edit a member's student details or change their fish/plant. Changing the
 * asset never touches scores or growth.
 */
class UpdateHabitatMemberRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->route('habitat'));
    }

    public function member(): HabitatMember
    {
        return $this->route('member');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $member = $this->member();

        return [
            'name' => ['required', 'string', 'max:120'],
            'student_number' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique(Student::class)->where('user_id', $this->user()->id)->ignore($member->student_id),
            ],
            'photo' => ['nullable', 'image', 'max:2048'],
            'remove_photo' => ['boolean'],
            'game_asset_id' => [
                'required',
                'integer',
                // Inactive assets stay valid for the member already using them.
                Rule::exists(GameAsset::class, 'id')->where(fn (Builder $query) => $query
                    ->where('type', $this->route('habitat')->type->assetType()->value)
                    ->where(fn (Builder $query) => $query->where('is_active', true)->orWhere('id', $member->game_asset_id))),
            ],
        ];
    }
}
