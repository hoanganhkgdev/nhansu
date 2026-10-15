<?php

namespace App\Filament\Resources\Parts\Pages;

use App\Filament\Resources\Parts\PartResource;
use App\Filament\Widgets\PartStats;
use App\Models\Part;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListParts extends ListRecords
{
    protected static string $resource = PartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [PartStats::class];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tất cả')->badge(Part::count()),
            'shown' => Tab::make('Đang hiển thị')->badge(Part::where('is_active', true)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)),
            'empty' => Tab::make('Chưa có nhân sự')->badge(Part::doesntHave('officials')->count())->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->doesntHave('officials')),
            'hidden' => Tab::make('Đang ẩn')->badge(Part::where('is_active', false)->count())->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)),
        ];
    }
}
