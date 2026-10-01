<?php

namespace Database\Factories;

use App\Enums\AnswerType;
use App\Enums\FieldType;
use App\Enums\OutputSection;
use App\Enums\TemplateStatus;
use App\Models\FormTemplate;
use App\Models\Question;
use App\Models\QuestionGroup;
use App\Models\TemplateField;
use App\Models\TransportMode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FormTemplate>
 */
class FormTemplateFactory extends Factory
{
    protected $model = FormTemplate::class;

    public function definition(): array
    {
        $name = 'Template '.fake()->unique()->word();

        return [
            'transport_mode_id' => TransportMode::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'version' => 1,
            'description' => fake()->sentence(),
            'status' => TemplateStatus::Draft,
            'scoring_enabled' => true,
            'scoring_strategy' => 'weighted',
            'passing_score' => 75,
            'max_evidence_per_answer' => 5,
            'locale' => 'id',
            'published_at' => null,
            'created_by' => null,
        ];
    }

    public function published(): static
    {
        return $this->state([
            'status' => TemplateStatus::Published,
            'published_at' => now(),
        ]);
    }

    /** Template dengan n indikator × m pertanyaan boolean. */
    public function withChecklist(int $groups = 2, int $questions = 3): static
    {
        return $this->afterCreating(function (FormTemplate $template) use ($groups, $questions) {
            for ($g = 1; $g <= $groups; $g++) {
                $group = QuestionGroup::factory()->create([
                    'form_template_id' => $template->id,
                    'name' => "Indikator {$g}",
                    'output_section' => OutputSection::Checklist,
                    'sort_order' => $g,
                ]);

                for ($q = 1; $q <= $questions; $q++) {
                    Question::factory()->create([
                        'form_template_id' => $template->id,
                        'question_group_id' => $group->id,
                        'text' => "Pertanyaan {$g}.{$q}",
                        'answer_type' => AnswerType::Boolean,
                        'max_score' => 1,
                        'weight' => 1,
                        'sort_order' => $q,
                    ]);
                }
            }
        });
    }

    /** Template dengan field profil dinamis. */
    public function withFields(array $fields = ['nama_aset' => 'Nama Aset', 'asal' => 'Asal']): static
    {
        return $this->afterCreating(function (FormTemplate $template) use ($fields) {
            $i = 0;

            foreach ($fields as $key => $label) {
                TemplateField::factory()->create([
                    'form_template_id' => $template->id,
                    'key' => $key,
                    'label' => $label,
                    'field_type' => FieldType::Text,
                    'sort_order' => ++$i,
                    'show_in_pdf' => true,
                ]);
            }
        });
    }
}
