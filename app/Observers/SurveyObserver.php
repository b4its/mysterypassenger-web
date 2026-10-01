<?php

namespace App\Observers;

use App\Models\Survey;
use Illuminate\Support\Facades\Storage;

class SurveyObserver
{
    public function deleted(Survey $survey): void
    {
        // soft delete → file media dibiarkan agar bisa dipulihkan.
    }

    public function forceDeleted(Survey $survey): void
    {
        Storage::disk('survey_media')->deleteDirectory($survey->uuid);
    }
}
