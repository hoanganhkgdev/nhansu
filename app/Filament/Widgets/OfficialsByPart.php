<?php

namespace App\Filament\Widgets;

use App\Models\Part;
use Filament\Widgets\ChartWidget;

class OfficialsByPart extends ChartWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 2;

    protected ?string $heading = 'Nhân sự theo tỉnh/thành';

    protected ?string $description = '10 nơi có nhiều nhân sự nhất';

    protected ?string $maxHeight = '320px';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $parts = Part::withCount('officials')->orderByDesc('officials_count')->limit(10)->get();

        return [
            'datasets' => [[
                'label' => 'Nhân sự',
                'data' => $parts->pluck('officials_count')->all(),
                'backgroundColor' => '#009444',
                'borderRadius' => 6,
                'maxBarThickness' => 22,
            ]],
            'labels' => $parts->pluck('title')->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'x' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
                'y' => ['grid' => ['display' => false]],
            ],
        ];
    }
}
