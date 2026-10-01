<?php

namespace App\Filament\Widgets;

use App\Models\Survey;
use App\Models\TransportMode;
use Filament\Widgets\ChartWidget;

class SurveysPerModeChart extends ChartWidget
{
    protected ?string $heading = 'Survei per Moda Transportasi';

    protected ?string $description = 'Distribusi jumlah survei menurut jenis transportasi.';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $user = auth()->user();

        $modes = TransportMode::query()
            ->orderBy('sort_order')
            ->pluck('name', 'id');

        $counts = Survey::query()
            ->visibleTo($user)
            ->selectRaw('transport_mode_id, COUNT(*) as total')
            ->groupBy('transport_mode_id')
            ->pluck('total', 'transport_mode_id');

        $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'];

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Survei',
                    'data' => $modes->keys()->map(fn ($id) => (int) ($counts[$id] ?? 0))->all(),
                    'backgroundColor' => collect($modes)->keys()
                        ->map(fn ($i) => $colors[$i % count($colors)])
                        ->all(),
                ],
            ],
            'labels' => $modes->values()->all(),
        ];
    }
}
