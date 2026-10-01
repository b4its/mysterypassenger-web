<?php

use App\Filament\Resources\FormTemplates\Pages\CreateFormTemplate;
use App\Filament\Resources\FormTemplates\Pages\EditFormTemplate;
use App\Filament\Resources\FormTemplates\Pages\ListFormTemplates;
use App\Filament\Resources\ReportSettings\Pages\EditReportSetting;
use App\Filament\Resources\ReportSettings\Pages\ListReportSettings;
use App\Filament\Resources\Surveys\Pages\CreateSurvey;
use App\Filament\Resources\Surveys\Pages\ListSurveys;
use App\Filament\Resources\Surveys\Pages\ViewSurvey;
use App\Filament\Resources\TransportModes\Pages\EditTransportMode;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\FormTemplate;
use App\Models\ReportSetting;
use App\Models\Survey;
use App\Models\TransportMode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Livewire\livewire;

beforeEach(fn () => actingAsAdmin());

it('merender daftar template formulir', function () {
    $templates = FormTemplate::factory()->count(2)->create();

    livewire(ListFormTemplates::class)
        ->assertOk()
        ->assertCanSeeTableRecords($templates);
});

it('membuat template formulir baru', function () {
    $mode = TransportMode::factory()->create();

    livewire(CreateFormTemplate::class)
        ->fillForm([
            'transport_mode_id' => $mode->id,
            'name' => 'Template Baru',
            'slug' => 'template-baru',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(FormTemplate::where('slug', 'template-baru')->exists())->toBeTrue();
});

it('mengubah template formulir', function () {
    $template = FormTemplate::factory()->create();

    livewire(EditFormTemplate::class, ['record' => $template->getKey()])
        ->fillForm(['name' => 'Nama Baru'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($template->refresh()->name)->toBe('Nama Baru');
});

it('merender daftar pengguna', function () {
    $users = User::factory()->count(3)->create();

    livewire(ListUsers::class)
        ->assertOk()
        ->assertCanSeeTableRecords($users);
});

it('membuat pengguna baru dengan password ter-hash', function () {
    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Pengguna Baru',
            'email' => 'baru@example.test',
            'role' => 'surveyor',
            'password' => 'rahasia123',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::where('email', 'baru@example.test')->first();

    expect($user)->not->toBeNull()
        ->and($user->role->value)->toBe('surveyor')
        ->and(Hash::check('rahasia123', $user->password))->toBeTrue();
});

it('mengubah pengguna tanpa mengubah password bila dikosongkan', function () {
    $user = User::factory()->create();
    $originalHash = $user->password;

    livewire(EditUser::class, ['record' => $user->getKey()])
        ->fillForm(['name' => 'Nama Diubah', 'password' => ''])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->refresh()->name)->toBe('Nama Diubah')
        ->and($user->password)->toBe($originalHash);
});

it('mengubah moda transportasi', function () {
    $mode = TransportMode::factory()->create();

    livewire(EditTransportMode::class, ['record' => $mode->getKey()])
        ->fillForm(['name' => 'Moda Diubah'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($mode->refresh()->name)->toBe('Moda Diubah');
});

it('merender daftar pengaturan cetak', function () {
    $setting = ReportSetting::factory()->create();

    livewire(ListReportSettings::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$setting]);
});

it('mengubah pengaturan cetak', function () {
    $setting = ReportSetting::factory()->create();

    livewire(EditReportSetting::class, ['record' => $setting->getKey()])
        ->fillForm(['organization_name' => 'Instansi Baru'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($setting->refresh()->organization_name)->toBe('Instansi Baru');
});

it('merender wizard pembuatan survei', function () {
    livewire(CreateSurvey::class)
        ->assertOk();
});

it('merender halaman detail survei', function () {
    $template = FormTemplate::factory()->published()->withFields()->withChecklist(1, 2)->create();
    $survey = Survey::factory()->for($template)->submitted()->withAnswers(1, 2)->create();

    livewire(ViewSurvey::class, ['record' => $survey->getKey()])
        ->assertOk();
});

it('merender daftar survei untuk admin', function () {
    Survey::factory()->count(2)->create();

    livewire(ListSurveys::class)->assertOk();
});
