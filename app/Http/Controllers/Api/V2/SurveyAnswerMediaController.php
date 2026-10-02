<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V2\SurveyAnswerMediaResource;
use App\Models\Question;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SurveyAnswerMediaController extends Controller
{
    public function store(Request $request, Survey $survey, Question $question): JsonResponse
    {
        $this->authorize('update', $survey);

        if ($question->form_template_id !== $survey->form_template_id) {
            abort(422, 'Pertanyaan bukan bagian dari template survei ini.');
        }

        $request->validate([
            'file' => ['required', 'file', 'image', 'mimes:jpeg,png,webp,jpg', 'max:5120'],
        ]);

        $file = $request->file('file');

        /** @var SurveyAnswer $answer */
        $answer = SurveyAnswer::firstOrCreate(
            ['survey_id' => $survey->id, 'question_id' => $question->id],
            [
                'question_group_id' => $question->question_group_id,
                'answer_type' => $question->answer_type,
                'max_score' => $question->max_score,
                'answered_at' => now(),
            ],
        );

        $currentCount = $answer->media()->count();
        if ($currentCount >= $question->evidence_max) {
            throw ValidationException::withMessages([
                'file' => ["Batas maksimal {$question->evidence_max} bukti foto telah tercapai."],
            ]);
        }

        $path = $file->store($survey->uuid, 'survey_media');

        $media = $answer->media()->create([
            'disk' => 'survey_media',
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'sort_order' => $currentCount,
        ]);

        return SurveyAnswerMediaResource::make($media)->response()->setStatusCode(201);
    }
}
