<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Officials\OfficialResource;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Bảng điều khiển';

    public function getSubheading(): ?string
    {
        $hour = (int) now()->format('G');
        $greeting = $hour < 11 ? 'Chào buổi sáng' : ($hour < 14 ? 'Chào buổi trưa' : ($hour < 18 ? 'Chào buổi chiều' : 'Chào buổi tối'));

        return $greeting.', '.auth()->user()->name.'. Dưới đây là tình hình dữ liệu nhân sự hôm nay.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('list')->label('Danh sách nhân sự')->icon(Heroicon::OutlinedUserGroup)->color('gray')
                ->url(OfficialResource::getUrl('index')),
            Action::make('create')->label('Thêm nhân sự')->icon(Heroicon::OutlinedPlus)
                ->url(OfficialResource::getUrl('create')),
        ];
    }
}
