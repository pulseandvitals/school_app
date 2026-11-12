<?php

namespace App\Http\Requests;

use App\Models\Student;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StudentRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->route('student'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'student_number' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique(Student::class)->where('user_id', $this->user()->id)->ignore($this->route('student')),
            ],
            'photo' => ['nullable', 'image', 'max:2048'],
            'remove_photo' => ['boolean'],
        ];
    }
}
