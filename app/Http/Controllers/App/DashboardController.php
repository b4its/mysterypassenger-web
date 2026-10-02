<?php

namespace App\Http\Controllers\App;

use App\Enums\SurveyStatus;
use App\Http\Controllers\Controller;
use App\Models\Survey;
use Illuminate\Contracts\View\View;

/**
 * Beranda aplikasi (non-Filament) untuk surveyor & reviewer.
 *
 * Halaman ini SENGAJA tidak menyertakan pengelolaan pertanyaan/template —
 * itu wewenang admin di panel Filament.
 */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $base = Survey::query()->visibleTo($user);

        return view('app.dashboard', [
            'user' => $user,
            'summary' => [
                'total' => (clone $base)->count(),
                'draft' => (clone $base)->where('status', SurveyStatus::Draft)->count(),
                'submitted' => (clone $base)->where('status', SurveyStatus::Submitted)->count(),
                'approved' => (clone $base)->where('status', SurveyStatus::Approved)->count(),
                'rejected' => (clone $base)->where('status', SurveyStatus::Rejected)->count(),
            ],
            'latest' => (clone $base)
                ->with('transportMode')
                ->latest('executed_at')
                ->limit(5)
                ->get(),
        ]);
    }
}
