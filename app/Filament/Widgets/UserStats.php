<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Stat cards at the top of the Tài khoản page (not shown on the dashboard). */
class UserStats extends StatsOverviewWidget
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
        $never = User::whereNull('last_login_at')->where('is_active', true)->where('role', '!=', User::ROLE_NONE)->count();

        return [
            Stat::make('Tổng tài khoản', number_format(User::count()))
                ->description(User::whereIn('role', [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN])->count().' quản trị · '.User::where('role', User::ROLE_EDITOR)->count().' biên tập')
                ->descriptionIcon(Heroicon::OutlinedUsers),
            Stat::make('Đang hoạt động', number_format(User::where('is_active', true)->count()))
                ->description(User::where('is_active', false)->count().' đã khóa')
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->color('success'),
            Stat::make('Chưa từng đăng nhập', number_format($never))
                ->description($never ? 'Nhớ gửi mật khẩu cho họ' : 'Mọi người đều đã đăng nhập')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color($never ? 'warning' : 'success'),
        ];
    }
}
