<?php

namespace App\Filament\Resources\Parts\Schemas;

use App\Models\Part;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;

class PartForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(['default' => 1, 'lg' => 3])->columnSpanFull()->schema([
                    Grid::make(1)->columnSpan(['lg' => 2])->schema([
                        Section::make('Thông tin chung')->columns(2)->schema([
                            TextInput::make('title')->label('Tên')->required()->maxLength(255),
                            TextInput::make('slug')
                                ->label('Đường dẫn (alias)')
                                ->visibleOn('edit')
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('Tự sinh khi tạo, không sửa được để link cũ không bị hỏng.'),
                            Textarea::make('note')->label('Ghi chú')->columnSpanFull(),
                            Toggle::make('is_active')->label('Hiển thị công khai')->default(true),
                        ]),
                        Section::make('Liên hệ')->columns(2)->collapsible()->collapsed()->schema([
                            TextInput::make('office')->label('Văn phòng'),
                            TextInput::make('address')->label('Địa chỉ'),
                            TextInput::make('phone')->label('Điện thoại')->tel(),
                            TextInput::make('fax')->label('Fax'),
                            TextInput::make('website')->label('Website'),
                            TextInput::make('email')->label('Email')->email(),
                        ]),
                    ]),
                    Section::make('Thống kê')->columnSpan(['lg' => 1])->visibleOn('edit')->schema([
                        Text::make(fn (?Part $record) => $record
                            ? number_format($record->officials()->count()).' nhân sự' : '')->weight('bold')->size('lg'),
                        Text::make(function (?Part $record) {
                            $last = $record?->officials()->max('created_at');

                            return $last ? 'Nhập gần nhất: '.\Illuminate\Support\Carbon::parse($last)->locale('vi')->diffForHumans() : 'Chưa có nhân sự nào';
                        })->color('gray'),
                    ]),
                ]),
            ]);
    }
}
