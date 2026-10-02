<?php

use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\SurveyController as AppSurveyController;
use App\Http\Controllers\App\SurveyFillController;
use App\Http\Controllers\App\SurveyReviewController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ExportDownloadController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\SurveyMediaController;
use App\Http\Controllers\SurveyPdfController;
use App\Http\Controllers\SurveyPrintController;
use App\Http\Controllers\TemplatePreviewController;
use Illuminate\Support\Facades\Route;

// Halaman selamat datang publik
Route::get('/', LandingController::class)->name('landing');

// Autentikasi web non-Filament
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login'])->name('login.post');
});
Route::post('logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Halaman aplikasi web non-Filament (Surveyor & Reviewer)
Route::middleware(['auth'])->prefix('app')->name('app.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('surveys', [AppSurveyController::class, 'index'])->name('surveys.index');
    Route::get('surveys/create', [SurveyFillController::class, 'create'])->name('surveys.create');
    Route::post('surveys', [SurveyFillController::class, 'store'])->name('surveys.store');

    Route::get('surveys/{survey}', [AppSurveyController::class, 'show'])->name('surveys.show');
    Route::get('surveys/{survey}/fill', [SurveyFillController::class, 'fill'])->name('surveys.fill');
    Route::post('surveys/{survey}/fill', [SurveyFillController::class, 'update'])->name('surveys.update');

    Route::post('surveys/{survey}/review', [SurveyReviewController::class, 'review'])->name('surveys.review');
});

// Aset survei, cetak, dan berkas ekspor
Route::middleware(['auth'])->group(function () {
    Route::get('surveys/{survey}/pdf', SurveyPdfController::class)->name('surveys.pdf');
    Route::get('surveys/{survey}/print', SurveyPrintController::class)->name('surveys.print');
    Route::get('surveys/{survey}/media/{media}', SurveyMediaController::class)->name('surveys.media');
    Route::get('templates/{template}/preview', TemplatePreviewController::class)->name('templates.preview');
    Route::get('exports/download', ExportDownloadController::class)->name('exports.download');
});
