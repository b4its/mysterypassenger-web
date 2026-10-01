<?php

namespace App\Filament\Widgets;

use App\Enums\SurveyStatus;
use App\Models\Survey;
use App\Models\TransportMode;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SurveyStatsOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();
        $base = Survey::query()->visibleTo($user);

        return [
            Stat::make('Total Survei', (clone $base)->count())
                ->description('Seluruh periode')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('primary'),

            Stat::make('Bulan Ini', (clone $base)
                ->whereMonth('executed_at', now()->month)
                ->whereYear('executed_at', now()->year)
                ->count())
                ->description(now()->translatedFormat('F Y'))
                ->icon('heroicon-o-calendar-days')
                ->color('info'),

            Stat::make('Menunggu Review', (clone $base)
                ->where('status', SurveyStatus::Submitted)
                ->count())
                ->description('Perlu tindakan reviewer')
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Moda Aktif', TransportMode::where('is_active', true)->count())
                ->description('Jenis transportasi terdaftar')
                ->icon('heroicon-o-truck')
                ->color('success'),
        ];
    }
}
