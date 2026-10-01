<?php

namespace App\Observers;

use App\Models\SurveyAnswerMedia;
use Illuminate\Support\Facades\Storage;

class SurveyAnswerMediaObserver
{
    public function deleted(SurveyAnswerMedia $media): void
    {
        Storage::disk($media->disk)->delete($media->path);
    }
}
