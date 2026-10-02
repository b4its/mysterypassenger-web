<?php

use App\Http\Controllers\Api\Legacy\LegacyAuthController;
use App\Http\Controllers\Api\Legacy\LegacyMasterPertanyaanController;
use App\Http\Controllers\Api\Legacy\LegacyPelaporanController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [LegacyAuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/master-pertanyaan/get', [LegacyMasterPertanyaanController::class, 'index']);
    Route::get('/master-pertanyaan/ceklist/get', [LegacyMasterPertanyaanController::class, 'checklist']);
    Route::get('/master-pertanyaan/laporan/get', [LegacyMasterPertanyaanController::class, 'laporan']);
    Route::any('/master-pertanyaan/store', [LegacyMasterPertanyaanController::class, 'gone']);
    Route::any('/master-pertanyaan/update/{id}', [LegacyMasterPertanyaanController::class, 'gone']);
    Route::any('/master-pertanyaan/delete/{id}', [LegacyMasterPertanyaanController::class, 'gone']);
    Route::any('/list-pertanyaan/{any?}', [LegacyMasterPertanyaanController::class, 'gone'])->where('any', '.*');

    Route::get('/pelaporan/get', [LegacyPelaporanController::class, 'index']);
    Route::get('/pelaporan/get/{id}', [LegacyPelaporanController::class, 'show']);
    Route::post('/pelaporan/store', [LegacyPelaporanController::class, 'store']);
    Route::get('/media/{path}', [LegacyPelaporanController::class, 'media'])->where('path', '.*');
});
