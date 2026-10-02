<?php

namespace App\Http\Resources\Api\V2;

use App\Models\SurveyAnswerMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SurveyAnswerMedia
 */
class SurveyAnswerMediaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $survey = $this->surveyAnswer?->survey;

        return [
            'id' => $this->id,
            'survey_answer_id' => $this->survey_answer_id,
            'path' => $this->path,
            'url' => $survey ? route('surveys.media', ['survey' => $survey, 'media' => $this->id]) : null,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size ? (int) $this->file_size : null,
            'sort_order' => (int) $this->sort_order,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
