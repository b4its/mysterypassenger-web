<?php

namespace App\Http\Controllers\Api\V2;

use App\Enums\OutputSection;
use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Services\SurveyPdfRenderer;
use App\Services\SurveyReportComposer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SurveyPdfController extends Controller
{
    public function __invoke(
        Request $request,
        Survey $survey,
        SurveyReportComposer $composer,
        SurveyPdfRenderer $renderer,
    ): Response {
        $this->authorize('print', $survey);

        $section = OutputSection::tryFrom((string) $request->query('section', 'checklist'))
            ?? OutputSection::Checklist;

        $data = $composer->compose($survey, $section);
        $pdf = $renderer->render($data);

        $filename = str($survey->code)->replace('/', '-')
            ->append('-', $section->value, '.pdf')
            ->toString();

        return $request->boolean('inline', true)
            ? $pdf->stream($filename)
            : $pdf->download($filename);
    }
}
