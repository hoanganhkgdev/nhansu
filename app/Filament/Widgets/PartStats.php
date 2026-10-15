<?php

namespace App\Filament\Widgets;

use App\Models\Part;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Thẻ thống kê riêng cho trang Tỉnh/Thành (không hiện ở dashboard). */
class PartStats extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $parts = Part::withCount('officials')->get();
        $top = $parts->sortByDesc('officials_count')->first();
        $empty = $parts->where('officials_count', 0)->count();

        return [
            Stat::make('Tổng số', number_format($parts->count()))
                ->description($parts->where('is_active', true)->count().' đang hiển thị')
                ->descriptionIcon(Heroicon::OutlinedBuildingOffice2),
            Stat::make('Chưa có nhân sự', number_format($empty))
                ->description($empty ? 'Xem ở tab "Chưa có nhân sự"' : 'Mục nào cũng có dữ liệu')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color($empty ? 'warning' : 'success'),
            Stat::make('Đông nhất', $top ? number_format($top->officials_count) : '0')
                ->description($top?->title ?? '—')
                ->descriptionIcon(Heroicon::OutlinedTrophy)
                ->color('success'),
        ];
    }
}
