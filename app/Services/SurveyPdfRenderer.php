<?php

namespace App\Services;

use App\Enums\OutputSection;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfInstance;

class SurveyPdfRenderer
{
    /**
     * @param  array<string, mixed>  $data  hasil SurveyReportComposer::compose()
     */
    public function render(array $data): PdfInstance
    {
        $setting = $data['setting'];

        $view = $data['section'] === OutputSection::Report
            ? 'pdf.survey-report'
            : 'pdf.survey-checklist';

        return Pdf::loadView($view, $data)
            ->setPaper($setting->paper_size ?: 'a4', $setting->orientation ?: 'landscape')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isFontSubsettingEnabled', true);
    }
}
