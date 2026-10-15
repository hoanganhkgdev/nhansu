<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\UserStats;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [UserStats::class];
    }

    private static function neverLoggedIn(): Builder
    {
        return User::whereNull('last_login_at')->where('is_active', true)->where('role', '!=', User::ROLE_NONE);
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tất cả')->badge(User::count()),
            'active' => Tab::make('Đang hoạt động')->badge(User::where('is_active', true)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)),
            'locked' => Tab::make('Đã khóa')->badge(User::where('is_active', false)->count())->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)),
            'never' => Tab::make('Chưa từng đăng nhập')->badge(self::neverLoggedIn()->count())->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('last_login_at')->where('is_active', true)->where('role', '!=', User::ROLE_NONE)),
        ];
    }
}
