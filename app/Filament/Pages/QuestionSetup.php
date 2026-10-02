<?php

namespace App\Filament\Pages;

use App\Enums\TemplateStatus;
use App\Filament\Resources\FormTemplates\FormTemplateResource;
use App\Filament\Resources\TransportModes\TransportModeResource;
use App\Models\FormTemplate;
use App\Models\Question;
use App\Models\QuestionGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Pengaturan Pertanyaan — mereplikasi hub "Pengaturan" pada aplikasi Android v1:
 * mengelola Pertanyaan Utama (template), Pertanyaan Ceklis/Laporan, dan
 * membuat template baru. Titik masuk menuju builder template Filament.
 */
class QuestionSetup extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $navigationLabel = 'Pengaturan Pertanyaan';

    protected static string|\UnitEnum|null $navigationGroup = 'Konfigurasi Formulir';

    protected static ?string $title = 'Pengaturan Pertanyaan';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.question-setup';

    /** @return array<int, array<string, mixed>> */
    public function getSetupItems(): array
    {
        return [
            [
                'key' => 'utama',
                'title' => 'Pertanyaan Utama',
                'description' => 'Kelola template formulir & indikator utamanya.',
                'icon' => 'document-text',
                'color' => 'warning',
                'url' => FormTemplateResource::getUrl('index'),
            ],
            [
                'key' => 'ceklist',
                'title' => 'Pertanyaan Ceklis / Laporan',
                'description' => 'Susun pertanyaan pada indikator (checklist maupun laporan).',
                'icon' => 'check-circle',
                'color' => 'success',
                'url' => FormTemplateResource::getUrl('index'),
            ],
            [
                'key' => 'buat',
                'title' => 'Buat Baru',
                'description' => 'Buat template formulir baru untuk sebuah moda.',
                'icon' => 'plus-circle',
                'color' => 'primary',
                'url' => FormTemplateResource::getUrl('create'),
            ],
            [
                'key' => 'moda',
                'title' => 'Jenis Kendaraan',
                'description' => 'Tambahkan atau kurangi jenis transportasi.',
                'icon' => 'truck',
                'color' => 'info',
                'url' => TransportModeResource::getUrl('index'),
            ],
        ];
    }

    /** @return array<string, int> */
    public function getStats(): array
    {
        return [
            'templates' => FormTemplate::query()->where('status', TemplateStatus::Published)->count(),
            'groups' => QuestionGroup::query()->count(),
            'questions' => Question::query()->count(),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
