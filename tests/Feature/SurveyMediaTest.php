<?php

use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyAnswerMedia;
use Illuminate\Support\Facades\Storage;

it('menyajikan media kepada pemilik survei', function () {
    Storage::fake('survey_media');

    $surveyor = actingAsSurveyor();
    $survey = Survey::factory()->create(['user_id' => $surveyor->id]);
    $answer = SurveyAnswer::factory()->create(['survey_id' => $survey->id]);

    Storage::disk('survey_media')->put('a/b/photo.jpg', 'binary');

    $media = SurveyAnswerMedia::factory()->create([
        'survey_answer_id' => $answer->id,
        'disk' => 'survey_media',
        'path' => 'a/b/photo.jpg',
    ]);

    $this->get(route('surveys.media', ['survey' => $survey, 'media' => $media]))
        ->assertOk();
});

it('menolak media kepada surveyor lain', function () {
    Storage::fake('survey_media');

    $survey = Survey::factory()->create();
    $answer = SurveyAnswer::factory()->create(['survey_id' => $survey->id]);
    Storage::disk('survey_media')->put('a/b/photo.jpg', 'binary');
    $media = SurveyAnswerMedia::factory()->create([
        'survey_answer_id' => $answer->id,
        'path' => 'a/b/photo.jpg',
    ]);

    actingAsSurveyor();

    $this->get(route('surveys.media', ['survey' => $survey, 'media' => $media]))
        ->assertForbidden();
});

it('menolak ketika id media bukan milik survei pada URL (IDOR)', function () {
    Storage::fake('survey_media');

    $surveyor = actingAsSurveyor();
    $mine = Survey::factory()->create(['user_id' => $surveyor->id]);

    $foreignAnswer = SurveyAnswer::factory()->create();
    $foreignMedia = SurveyAnswerMedia::factory()->create(['survey_answer_id' => $foreignAnswer->id]);

    $this->get(route('surveys.media', ['survey' => $mine, 'media' => $foreignMedia]))
        ->assertNotFound();
});

it('menghapus file fisik saat record media dihapus', function () {
    Storage::fake('survey_media');

    $answer = SurveyAnswer::factory()->create();
    Storage::disk('survey_media')->put('a/b/c.jpg', 'x');

    $media = SurveyAnswerMedia::factory()->create([
        'survey_answer_id' => $answer->id,
        'disk' => 'survey_media',
        'path' => 'a/b/c.jpg',
    ]);

    $media->delete();

    Storage::disk('survey_media')->assertMissing('a/b/c.jpg');
});

it('menolak media tanpa autentikasi', function () {
    $survey = Survey::factory()->create();
    $answer = SurveyAnswer::factory()->create(['survey_id' => $survey->id]);
    $media = SurveyAnswerMedia::factory()->create(['survey_answer_id' => $answer->id]);

    $this->get(route('surveys.media', ['survey' => $survey, 'media' => $media]))
        ->assertRedirect();
});
