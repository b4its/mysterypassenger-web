<?php

namespace App\Http\Requests\Api\V2;

use App\Models\Survey;
use App\Support\TemplateFieldRuleBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSurveyRequest extends FormRequest
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
        /** @var Survey|null $survey */
        $survey = $this->route('survey');
        $template = $survey?->formTemplate()->with(['fields', 'questions'])->first();

        $rules = [
            'evaluator_name' => ['sometimes', 'required', 'string', 'max:160'],
            'executed_at' => ['sometimes', 'required', 'date', 'before_or_equal:now'],
            'location_text' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'summary_note' => ['nullable', 'string'],
            'submit' => ['nullable', 'boolean'],
            'fields' => ['nullable', 'array'],
            'answers' => ['nullable', 'array'],
            'answers.*.question_id' => ['required_with:answers', 'integer'],
        ];

        if ($template) {
            $builder = app(TemplateFieldRuleBuilder::class);

            foreach ($template->fields as $field) {
                $fieldRules = $builder->rulesFor($field);
                if ($this->boolean('submit') && $field->is_required) {
                    array_unshift($fieldRules, 'required');
                } else {
                    array_unshift($fieldRules, 'nullable');
                }
                $rules["fields.{$field->key}"] = $fieldRules;
            }

            $questionIds = $template->questions->pluck('id')->all();
            if (count($questionIds) > 0) {
                $rules['answers.*.question_id'] = ['required_with:answers', 'integer', Rule::in($questionIds)];
            }
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    public function toFormState(): array
    {
        $state = [];

        foreach (['evaluator_name', 'executed_at', 'location_text', 'latitude', 'longitude', 'summary_note'] as $key) {
            if ($this->has($key)) {
                $state[$key] = $this->input($key);
            }
        }

        if ($this->has('fields')) {
            $state['fields'] = (array) $this->input('fields', []);
        }

        if ($this->has('answers')) {
            $answersMap = [];
            foreach ($this->input('answers', []) as $ans) {
                if (isset($ans['question_id'])) {
                    $answersMap[(int) $ans['question_id']] = [
                        'value' => $ans['value'] ?? null,
                        'note' => $ans['note'] ?? null,
                        'media' => $ans['media'] ?? [],
                    ];
                }
            }
            $state['answers'] = $answersMap;
        }

        return $state;
    }
}
