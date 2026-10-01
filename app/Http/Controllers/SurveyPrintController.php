<?php

namespace App\Http\Controllers;

use App\Enums\OutputSection;
use App\Models\Survey;
use App\Services\SurveyReportComposer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SurveyPrintController extends Controller
{
    public function __invoke(Request $request, Survey $survey, SurveyReportComposer $composer): View
    {
        $this->authorize('print', $survey);

        $section = OutputSection::tryFrom((string) $request->query('section', 'checklist'))
            ?? OutputSection::Checklist;

        return view('print.survey', $composer->compose($survey, $section) + [
            'autoPrint' => $request->boolean('auto', true),
        ]);
    }
}
