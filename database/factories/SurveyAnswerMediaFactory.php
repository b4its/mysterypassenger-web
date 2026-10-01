<?php

namespace Database\Factories;

use App\Models\SurveyAnswer;
use App\Models\SurveyAnswerMedia;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SurveyAnswerMedia>
 */
class SurveyAnswerMediaFactory extends Factory
{
    protected $model = SurveyAnswerMedia::class;

    public function definition(): array
    {
        return [
            'survey_answer_id' => SurveyAnswer::factory(),
            'disk' => 'survey_media',
            'path' => 'media/'.Str::ulid().'.jpg',
            'original_name' => 'bukti.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'width' => 800,
            'height' => 600,
            'caption' => null,
            'sort_order' => 0,
        ];
    }
}
