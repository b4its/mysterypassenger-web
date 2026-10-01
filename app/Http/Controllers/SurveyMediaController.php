<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\SurveyAnswerMedia;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SurveyMediaController extends Controller
{
    public function __invoke(Survey $survey, SurveyAnswerMedia $media): StreamedResponse
    {
        $this->authorize('view', $survey);

        abort_unless($media->surveyAnswer->survey_id === $survey->id, 404);

        $disk = Storage::disk($media->disk);

        abort_unless($disk->exists($media->path), 404);

        return $disk->response(
            $media->path,
            $media->original_name,
            ['Cache-Control' => 'private, max-age=3600'],
        );
    }
}
