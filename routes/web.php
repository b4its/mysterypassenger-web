<?php

use App\Http\Controllers\ExportDownloadController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\SurveyMediaController;
use App\Http\Controllers\SurveyPdfController;
use App\Http\Controllers\SurveyPrintController;
use App\Http\Controllers\TemplatePreviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('landing');

Route::middleware(['auth'])->group(function () {
    Route::get('surveys/{survey}/pdf', SurveyPdfController::class)->name('surveys.pdf');
    Route::get('surveys/{survey}/print', SurveyPrintController::class)->name('surveys.print');
    Route::get('surveys/{survey}/media/{media}', SurveyMediaController::class)->name('surveys.media');
    Route::get('templates/{template}/preview', TemplatePreviewController::class)->name('templates.preview');
    Route::get('exports/download', ExportDownloadController::class)->name('exports.download');
});
