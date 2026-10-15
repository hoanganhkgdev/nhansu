<?php

namespace App\Filament\Resources\Officials\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;

class OfficialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(['default' => 1, 'lg' => 3])->columnSpanFull()->schema([
                    Tabs::make('Nhân sự')->columnSpan(['lg' => 2])->persistTabInQueryString()->tabs([
                        Tab::make('Thông tin cá nhân')->icon('heroicon-o-user')->columns(2)->schema([
                            TextInput::make('fullname')->label('Họ và tên')->required()->maxLength(255),
                            TextInput::make('dharma_name')->label('Pháp danh')->maxLength(255),
                            DatePicker::make('birthday')->label('Ngày sinh')->native(false)->displayFormat('d/m/Y')
                                ->maxDate(now())->closeOnDateSelection(),
                            TextInput::make('member_code')->label('Mã số')->maxLength(50),
                            TextInput::make('resident')->label('Địa chỉ thường trú')->columnSpanFull(),
                        ]),
                        Tab::make('Chức vụ & phẩm trật')->icon('heroicon-o-building-office-2')->columns(2)->schema([
                            Select::make('part_id')->label('Tỉnh/Thành')
                                ->relationship('part', 'title')->searchable()->preload()->columnSpanFull(),
                            TextInput::make('position')->label('Chức vụ')->columnSpanFull(),
                            TextInput::make('precept_rank')->label('Giới phẩm')->maxLength(50),
                            TextInput::make('clergy_rank')->label('Giáo phẩm')->maxLength(100),
                            TextInput::make('education')->label('Trình độ học vấn')->columnSpanFull(),
                        ]),
                        Tab::make('Liên hệ & giấy tờ')->icon('heroicon-o-lock-closed')->columns(2)->schema([
                            TextInput::make('email')->label('Email')->email(),
                            TextInput::make('phone')->label('Điện thoại')->tel(),
                            TextInput::make('id_number')->label('Số CMND/CCCD'),
                            DatePicker::make('id_issued_date')->label('Ngày cấp'),
                            TextInput::make('id_issued_place')->label('Nơi cấp')->columnSpanFull(),
                        ]),
                    ]),
                    Group::make([
                        Section::make('Ảnh & hiển thị')->schema([
                            FileUpload::make('image')->label('Ảnh chân dung')
                                ->disk('officials')->directory('nhan-su-cac-tinh-thanh')->image()->imageEditor()
                                ->getUploadedFileNameForStorageUsing(fn ($file) => str($file->getClientOriginalName())
                                    ->beforeLast('.')->slug()->append('-'.time().'.'.$file->getClientOriginalExtension())->toString()),
                            Toggle::make('is_active')->label('Hiển thị công khai')->default(true),
                            Toggle::make('is_priority')->label('Ưu tiên lên đầu danh sách'),
                            TextInput::make('sort')->label('Thứ tự')->numeric()->default(0),
                        ]),
                        Section::make('Lưu ý')->compact()->schema([
                            Text::make('Số CMND, điện thoại và email chỉ hiện trong admin, không hiển thị ở trang công khai.')->color('gray'),
                        ]),
                    ])->columnSpan(['lg' => 1])->extraAttributes(['class' => 'lg:sticky lg:top-4 self-start']),
                ]),
            ]);
    }
}
