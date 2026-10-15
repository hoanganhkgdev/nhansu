<?php

namespace App\Filament\Widgets;

use App\Models\Official;
use App\Models\Part;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OfficialStats extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $total = Official::count();
        $shown = Official::where('is_active', true)->count();
        $withImage = Official::where('image', '!=', '')->count();
        $recent = Official::where('created_at', '>=', now()->subDays(30))->count();

        return [
            Stat::make('Tổng nhân sự', number_format($total))
                ->description('Tất cả bản ghi')
                ->descriptionIcon(Heroicon::OutlinedUserGroup),
            Stat::make('Đang hiển thị', number_format($shown))
                ->description($total ? round($shown / $total * 100).'% tổng số' : '—')
                ->descriptionIcon(Heroicon::OutlinedEye)
                ->color('success'),
            Stat::make('Đã có ảnh', number_format($withImage))
                ->description($total ? round($withImage / $total * 100).'% tổng số' : '—')
                ->descriptionIcon(Heroicon::OutlinedPhoto)
                ->color($withImage === $total ? 'success' : 'warning'),
            Stat::make('Thêm trong 30 ngày', number_format($recent))
                ->description(Part::where('is_active', true)->count().' tỉnh/thành đang hoạt động')
                ->descriptionIcon(Heroicon::OutlinedPlusCircle),
        ];
    }
}
