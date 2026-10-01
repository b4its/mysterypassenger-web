<?php

namespace App\Http\Controllers;

use App\Enums\OutputSection;
use App\Models\FormTemplate;
use App\Services\SurveyReportComposer;
use Illuminate\Contracts\View\View;

class TemplatePreviewController extends Controller
{
    /**
     * Pratinjau formulir/dokumen memakai data dummy dari template yang diminta.
     * Membantu admin menyetel kop surat tanpa membuat survei sungguhan.
     */
    public function __invoke(FormTemplate $template, SurveyReportComposer $composer): View
    {
        $this->authorize('view', $template);

        $survey = $template->surveys()->whereNotNull('meta')->latest()->first()
            ?? $template->surveys()->latest()->first();

        abort_if($survey === null, 404, 'Belum ada survei untuk template ini. Buat satu survei terlebih dahulu untuk pratinjau.');

        return view('print.survey', $composer->compose($survey, OutputSection::Checklist) + [
            'autoPrint' => false,
        ]);
    }
}
