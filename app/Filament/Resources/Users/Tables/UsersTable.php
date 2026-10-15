<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Support\Avatar;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->columns([
                ImageColumn::make('avatar')->label('')->circular()->size(32)
                    ->defaultImageUrl(fn (User $u) => Avatar::initials($u->name)),
                TextColumn::make('name')->label('Họ tên')->weight('semibold')
                    ->description(fn (User $u) => $u->email)
                    ->searchable(['name', 'email', 'username'])->sortable(),
                TextColumn::make('username')->label('Tên đăng nhập')->searchable()->color('gray'),
                TextColumn::make('role')->label('Vai trò')->badge()->sortable()
                    ->formatStateUsing(fn (string $state) => User::ROLES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        User::ROLE_SUPER_ADMIN => 'danger',
                        User::ROLE_ADMIN => 'primary',
                        User::ROLE_EDITOR => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('notes')->label('Lưu ý')->badge()->color('warning')
                    ->state(fn (User $u) => array_values(array_filter([
                        $u->is_active ? null : 'Đã khóa',
                        $u->hasLegacyPassword() ? 'Chưa đổi mật khẩu' : null,
                    ]))),
                TextColumn::make('last_login_at')->label('Đăng nhập gần nhất')->color('gray')->sortable()
                    ->state(fn (User $u) => $u->last_login_at?->locale('vi')->diffForHumans() ?? 'Chưa từng'),
                ToggleColumn::make('is_active')->label('Hoạt động')
                    ->disabled(fn (User $u) => $u->is(auth()->user())),
            ])
            ->filters([
                SelectFilter::make('role')->label('Vai trò')->options(User::ROLES),
            ])
            ->deferFilters(false)
            ->recordUrl(fn (User $u) => UserResource::getUrl('edit', ['record' => $u]))
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('reset')->label('Đặt lại mật khẩu')->icon('heroicon-o-key')
                        ->requiresConfirmation()
                        ->modalHeading(fn (User $u) => 'Đặt lại mật khẩu của '.$u->name)
                        ->modalDescription('Hệ thống tạo mật khẩu mới ngẫu nhiên và chỉ hiển thị một lần. Mật khẩu cũ sẽ không dùng được nữa.')
                        ->action(function (User $record) {
                            $password = Str::password(12, symbols: false);
                            $record->update(['password' => $password]);

                            Notification::make()->success()->persistent()
                                ->title('Mật khẩu mới của '.$record->name)
                                ->body(new HtmlString('<b style="font-size:1.1rem;letter-spacing:.05em">'.e($password).'</b><br>Chỉ hiển thị một lần, hãy gửi cho người dùng ngay.'))
                                ->send();
                        }),
                    DeleteAction::make()->hidden(fn (User $u) => $u->is(auth()->user())),
                ]),
            ]);
    }
}
