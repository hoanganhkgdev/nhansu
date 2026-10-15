<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(['default' => 1, 'lg' => 3])->columnSpanFull()->schema([
                    Section::make('Thông tin đăng nhập')->columnSpan(['lg' => 2])->columns(2)->schema([
                        TextInput::make('name')->label('Họ tên')->required()->maxLength(255),
                        TextInput::make('username')->label('Tên đăng nhập')->required()->maxLength(100)
                            ->unique(ignoreRecord: true)->alphaDash(),
                        TextInput::make('email')->label('Email')->email()->required()->maxLength(255)
                            ->unique(ignoreRecord: true)->columnSpanFull(),
                        TextInput::make('password')->label('Mật khẩu')->password()->revealable()
                            ->required(fn (string $operation) => $operation === 'create')
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->minLength(8)
                            ->helperText(fn (string $operation) => $operation === 'edit' ? 'Để trống nếu không đổi mật khẩu.' : 'Tối thiểu 8 ký tự.')
                            ->suffixAction(Action::make('generate')->icon('heroicon-o-sparkles')->tooltip('Tạo mật khẩu ngẫu nhiên')
                                ->action(function (Set $set) {
                                    $password = Str::password(12, symbols: false);
                                    $set('password', $password);
                                    $set('password_confirmation', $password);
                                })),
                        TextInput::make('password_confirmation')->label('Nhập lại mật khẩu')->password()->revealable()
                            ->same('password')->dehydrated(false)
                            ->required(fn (Get $get) => filled($get('password'))),
                    ]),
                    Section::make('Phân quyền')->columnSpan(['lg' => 1])->schema([
                        Select::make('role')->label('Vai trò')->options(User::ROLES)->required()
                            ->default(User::ROLE_EDITOR)->native(false)->live()
                            ->disabled(fn (?User $record) => ! auth()->user()?->isSuperAdmin() || $record?->is(auth()->user()))
                            ->dehydrated()
                            ->helperText(fn (?string $state) => User::ROLE_DESCRIPTIONS[$state] ?? null),
                        Toggle::make('is_active')->label('Cho phép đăng nhập')->default(true)
                            ->disabled(fn (?User $record) => $record?->is(auth()->user()))
                            ->dehydrated()
                            ->helperText('Tắt để khóa tài khoản nhưng vẫn giữ lại.'),
                        Text::make(fn (?User $record) => $record?->last_login_at
                            ? 'Đăng nhập gần nhất: '.$record->last_login_at->locale('vi')->diffForHumans()
                            : 'Chưa từng đăng nhập')->color('gray')->visibleOn('edit'),
                    ]),
                ]),
            ]);
    }
}
