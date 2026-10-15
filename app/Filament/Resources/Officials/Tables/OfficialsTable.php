<?php

namespace App\Filament\Resources\Officials\Tables;

use App\Filament\Resources\Officials\OfficialResource;
use App\Models\Official;
use App\Support\Avatar;
use App\Support\OfficialQr;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class OfficialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                ImageColumn::make('image')->label('')
                    ->disk('officials')->circular()->size(32)
                    ->defaultImageUrl(fn (Official $o) => Avatar::initials($o->dharma_name ?: $o->fullname)),
                TextColumn::make('fullname')->label('Họ tên / Pháp danh / Mã số')
                    ->weight('semibold')
                    ->description(fn (Official $o) => collect([$o->dharma_name, $o->member_code ? 'Mã '.$o->member_code : null])->filter()->implode(' · '))
                    ->searchable(['fullname', 'dharma_name', 'member_code'])
                    ->sortable(),
                TextColumn::make('part.title')->label('Tỉnh/Thành')->badge()->color('success')->sortable(),
                TextColumn::make('position')->label('Chức vụ')->limit(32)
                    ->tooltip(fn (Official $o) => $o->position)->searchable(),
                TextColumn::make('clergy_rank')->label('Giáo phẩm')->badge()->color('primary')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('birthday')->label('Ngày sinh')->date('d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('notes')->label('Lưu ý')->badge()->color('warning')->toggleable(isToggledHiddenByDefault: true)
                    ->state(fn (Official $o) => array_values(array_filter([
                        $o->is_active ? null : 'Đang ẩn',
                        $o->image !== '' ? null : 'Thiếu ảnh',
                        $o->part_id ? null : 'Chưa có đơn vị',
                    ]))),
                TextColumn::make('created_at')->label('Nhập lúc')->color('gray')->sortable()
                    ->since()->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('is_active')->label('Hiển thị'),
            ])
            ->filters([
                SelectFilter::make('part_id')->label('Tỉnh/Thành')->relationship('part', 'title')->searchable()->preload(),
                TernaryFilter::make('is_active')->label('Hiển thị'),
                TernaryFilter::make('has_image')->label('Có ảnh')
                    ->queries(
                        true: fn (Builder $query) => $query->where('image', '!=', ''),
                        false: fn (Builder $query) => $query->where('image', ''),
                    ),
            ])
            ->deferFilters(false)
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->recordUrl(fn (Official $o) => OfficialResource::getUrl('edit', ['record' => $o]))
            ->recordActions([
                Action::make('view')->label('Xem trang công khai')->icon('heroicon-o-arrow-top-right-on-square')->iconButton()->color('gray')
                    ->url(fn (Official $o) => route('officials.show', [$o->part?->slug ?? '-', $o->slug.'-'.$o->seq]))
                    ->openUrlInNewTab(),
                ActionGroup::make([
                    Action::make('qr')->label('Tải mã QR')->icon('heroicon-o-qr-code')
                        ->action(fn (Official $o) => response()->streamDownload(fn () => print (OfficialQr::png($o)), OfficialQr::filename($o), ['Content-Type' => 'image/png'])),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('show')->label('Hiển thị')->icon('heroicon-o-eye')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('hide')->label('Ẩn')->icon('heroicon-o-eye-slash')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('qr')->label('Xuất mã QR (ZIP)')->icon('heroicon-o-qr-code')
                        ->modalHeading('Xuất mã QR hàng loạt')
                        ->modalDescription(fn () => 'Tạo file ZIP chứa mã QR (PNG) cho các nhân sự đã chọn, đặt tên theo Mã số. Địa chỉ trong mã: '.(config('officials.qr_base_url') ?: config('app.url')).(OfficialQr::isLocalBase() ? ' — đây là địa chỉ nội bộ, hãy đặt QR_BASE_URL (hoặc APP_URL) thành địa chỉ trang thật trước khi in.' : ''))
                        ->requiresConfirmation()
                        ->modalSubmitActionLabel('Tạo file ZIP')
                        ->action(function (Collection $records) {
                            $path = OfficialQr::zip($records->load('part'));

                            return response()->download($path, 'ma-qr-nhan-su-'.now()->format('Ymd-His').'.zip')->deleteFileAfterSend();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
