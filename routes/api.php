<?php

use App\Http\Controllers\Api\V2\AuthController;
use App\Http\Controllers\Api\V2\FormTemplateController;
use App\Http\Controllers\Api\V2\ReviewController;
use App\Http\Controllers\Api\V2\SurveyAnswerMediaController;
use App\Http\Controllers\Api\V2\SurveyController;
use App\Http\Controllers\Api\V2\SurveyPdfController;
use App\Http\Controllers\Api\V2\TransportModeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v2 Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v2')->name('api.v2.')->group(function () {
    // Autentikasi
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');

        // Sinkronisasi struktur
        Route::get('/transport-modes', [TransportModeController::class, 'index'])->name('transport-modes.index');
        Route::get('/templates', [FormTemplateController::class, 'index'])->name('templates.index');
        Route::get('/templates/{template}', [FormTemplateController::class, 'show'])->name('templates.show');

        // Pengelolaan & pengisian survei
        Route::get('/surveys', [SurveyController::class, 'index'])->name('surveys.index');
        Route::post('/surveys', [SurveyController::class, 'store'])->name('surveys.store');
        Route::get('/surveys/{survey}', [SurveyController::class, 'show'])->name('surveys.show');
        Route::patch('/surveys/{survey}', [SurveyController::class, 'update'])->name('surveys.update');
        Route::post('/surveys/{survey}/submit', [SurveyController::class, 'submit'])->name('surveys.submit');
        Route::post('/surveys/{survey}/answers/{question}/media', [SurveyAnswerMediaController::class, 'store'])
            ->name('surveys.answers.media');
        Route::get('/surveys/{survey}/pdf', SurveyPdfController::class)->name('surveys.pdf');

        // Antrean review
        Route::prefix('review')->name('review.')->group(function () {
            Route::get('/surveys', [ReviewController::class, 'index'])->name('surveys.index');
            Route::post('/surveys/{survey}/transition', [ReviewController::class, 'transition'])->name('surveys.transition');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Legacy v1 Compatibility Routes (prefiks /api)
|--------------------------------------------------------------------------
*/
require __DIR__.'/api-legacy.php';
