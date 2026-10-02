<?php

namespace App\Http\Requests\Api\V2;

use App\Enums\SurveyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionSurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in([SurveyStatus::Approved->value, SurveyStatus::Rejected->value])],
            'note' => [
                Rule::requiredIf(fn () => $this->input('status') === SurveyStatus::Rejected->value),
                'nullable',
                'string',
            ],
        ];
    }
}
