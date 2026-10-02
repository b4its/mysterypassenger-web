<?php

namespace App\Filament\Pages;

use App\Enums\SurveyStatus;
use App\Filament\Resources\Surveys\SurveyResource;
use App\Filament\Resources\TransportModes\TransportModeResource;
use App\Filament\Widgets\LatestSurveysTable;
use App\Filament\Widgets\SurveysPerModeChart;
use App\Filament\Widgets\SurveyStatsOverview;
use App\Models\Survey;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;

/**
 * Beranda surveyor — mereplikasi alur dasbor aplikasi Android v1 sebagai
 * interaksi web penuh: kartu aksi utama (Mulai Pelaporan, Riwayat Pelaporan,
 * Pengaturan Pertanyaan) + ringkasan aktivitas.
 */
class Beranda extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Beranda';

    protected static ?string $title = 'Beranda';

    protected static ?int $navigationSort = -10;

    protected string $view = 'filament.pages.beranda';

    /** Jadikan Beranda sebagai halaman utama panel (route "/"). */
    public static function getRoutePath(Panel $panel): string
    {
        return '/';
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return '/';
    }

    /** Widget ringkasan (dipanggil dari Blade view). */
    public function getDashboardWidgets(): array
    {
        return [
            SurveyStatsOverview::class,
            SurveysPerModeChart::class,
            LatestSurveysTable::class,
        ];
    }

    /** Ringkasan kartu yang ditampilkan di beranda. */
    public function getCards(): array
    {
        $user = auth()->user();
        $canManageForms = $user?->isAdmin() ?? false;

        $cards = [
            [
                'key' => 'start',
                'title' => 'Mulai Pelaporan',
                'description' => 'Isi formulir temuan baru.',
                'icon' => 'pencil-square',
                'color' => 'primary',
                'url' => SurveyResource::getUrl('create'),
                'visible' => $user?->isAdmin() || $user?->isSurveyor(),
            ],
            [
                'key' => 'history',
                'title' => 'Riwayat Pelaporan',
                'description' => 'Lihat riwayat audit dan detailnya.',
                'icon' => 'clipboard-document-list',
                'color' => 'success',
                'url' => SurveyResource::getUrl('index'),
                'visible' => true,
            ],
            [
                'key' => 'settings',
                'title' => 'Pengaturan Pertanyaan',
                'description' => 'Susun indikator, pertanyaan, dan template formulir.',
                'icon' => 'adjustments-horizontal',
                'color' => 'warning',
                'url' => QuestionSetup::getUrl(),
                'visible' => $canManageForms,
            ],
            [
                'key' => 'modes',
                'title' => 'Jenis Kendaraan',
                'description' => 'Kelola moda transportasi (kapal, bus, kereta, …).',
                'icon' => 'truck',
                'color' => 'info',
                'url' => TransportModeResource::getUrl('index'),
                'visible' => $canManageForms,
            ],
        ];

        return array_values(array_filter($cards, fn (array $card) => $card['visible']));
    }

    /** Ringkasan angka aktivitas survei milik pengguna. */
    public function getSummary(): array
    {
        $base = Survey::query()->visibleTo(auth()->user());

        return [
            'total' => (clone $base)->count(),
            'draft' => (clone $base)->where('status', SurveyStatus::Draft)->count(),
            'submitted' => (clone $base)->where('status', SurveyStatus::Submitted)->count(),
            'approved' => (clone $base)->where('status', SurveyStatus::Approved)->count(),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public static function getNavigationLabel(): string
    {
        return 'Beranda';
    }
}
