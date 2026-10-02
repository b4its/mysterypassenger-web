<?php

namespace App\Http\Requests\Api\V2;

use App\Models\FormTemplate;
use App\Support\TemplateFieldRuleBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSurveyRequest extends FormRequest
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
        $template = FormTemplate::with(['fields', 'questions'])->find($this->input('form_template_id'));

        $rules = [
            'idempotency_key' => ['required', 'string', 'max:32'],
            'form_template_id' => ['required', 'integer', 'exists:form_templates,id'],
            'evaluator_name' => ['required', 'string', 'max:160'],
            'executed_at' => ['required', 'date', 'before_or_equal:now'],
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
     * Ubah payload API ke format form state yang dikonsumsi SurveySubmissionService.
     *
     * @return array{fields: array<string,mixed>, answers: array<int,array<string,mixed>>, evaluator_name: string, executed_at: mixed, location_text: ?string, latitude: ?float, longitude: ?float, summary_note: ?string}
     */
    public function toFormState(): array
    {
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

        return [
            'evaluator_name' => (string) $this->input('evaluator_name'),
            'executed_at' => $this->input('executed_at'),
            'location_text' => $this->input('location_text'),
            'latitude' => $this->input('latitude') !== null ? (float) $this->input('latitude') : null,
            'longitude' => $this->input('longitude') !== null ? (float) $this->input('longitude') : null,
            'summary_note' => $this->input('summary_note'),
            'fields' => (array) $this->input('fields', []),
            'answers' => $answersMap,
        ];
    }
}
