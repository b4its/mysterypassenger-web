<?php

namespace App\Http\Controllers;

use App\Enums\OutputSection;
use App\Models\FormTemplate;
use App\Services\SurveyReportComposer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class TemplatePreviewController extends Controller
{
    /**
     * Pratinjau dokumen/lembar ceklist dari sebuah template.
     *
     * Tidak memerlukan survei nyata — data contoh dibangun dari struktur
     * template sehingga admin dapat melihat hasil cetak untuk template apa pun
     * (termasuk yang belum pernah dipakai survei).
     */
    public function __invoke(Request $request, FormTemplate $template, SurveyReportComposer $composer): View
    {
        $this->authorize('view', $template);

        $section = OutputSection::tryFrom((string) $request->query('section', OutputSection::Checklist->value))
            ?? OutputSection::Checklist;

        return view('print.survey', $composer->composeForTemplate($template, $section) + [
            'autoPrint' => false,
        ]);
    }
}
