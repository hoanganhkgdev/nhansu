<?php

namespace App\Filament\Resources\Officials\Pages;

use App\Filament\Resources\Officials\OfficialResource;
use App\Models\Official;
use App\Models\Part;
use App\Support\OfficialImageImporter;
use App\Support\OfficialImporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Url;

class ListOfficials extends ListRecords
{
    protected static string $resource = OfficialResource::class;

    /** Cho phép liên kết kèm bộ lọc, ví dụ từ trang Tỉnh/Thành: ?tableFilters[part_id][value]=2 */
    #[Url]
    public ?array $tableFilters = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('template')
                ->label('Tải file mẫu')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    $path = tempnam(sys_get_temp_dir(), 'tpl');
                    OfficialImporter::writeTemplate($path);

                    return response()->download($path, 'mau-import-nhan-su.xlsx')->deleteFileAfterSend();
                }),
            Action::make('import')
                ->label('Nhập từ Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->modalHeading('Nhập nhân sự từ Excel')
                ->modalDescription('Cột được nhận theo tên tiêu đề (không phụ thuộc thứ tự). Bắt buộc có cột "Họ và tên". Dùng "Tải file mẫu" để lấy đúng tiêu đề. Ngày nhập theo dd/mm/yyyy.')
                ->modalSubmitActionLabel('Nhập')
                ->schema([
                    FileUpload::make('file')->label('File .xlsx hoặc .csv')->required()
                        ->disk('local')->directory('imports')->visibility('private')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'text/csv', 'text/plain', 'application/csv',
                        ]),
                    Select::make('part_id')->label('Tỉnh/Thành mặc định')
                        ->helperText('Dùng cho dòng có cột "Tỉnh/Thành" để trống. Dòng ghi tên không khớp sẽ bị bỏ qua.')
                        ->options(fn () => Part::orderBy('title')->pluck('title', 'id'))
                        ->searchable(),
                    Toggle::make('update')->label('Cập nhật nếu trùng Mã số')
                        ->helperText('Tắt: dòng trùng vẫn được thêm mới. Bật: ghi các ô có dữ liệu vào bản ghi cũ (ô trống không xóa dữ liệu).'),
                ])
                ->action(function (array $data) {
                    $path = Storage::disk('local')->path($data['file']);
                    try {
                        $r = (new OfficialImporter($data['part_id'] ?? null, (bool) ($data['update'] ?? false)))
                            ->import($path, pathinfo($path, PATHINFO_EXTENSION));
                    } finally {
                        Storage::disk('local')->delete($data['file']);
                    }

                    $body = "Thêm mới: {$r['created']} · Cập nhật: {$r['updated']} · Bỏ qua: {$r['skipped']}";
                    if ($r['errors']) {
                        $body .= '<br>'.e(implode(' | ', array_slice($r['errors'], 0, 15))).(count($r['errors']) > 15 ? ' …' : '');
                    }

                    Notification::make()
                        ->title('Nhập xong')
                        ->body(new HtmlString($body))
                        ->status($r['skipped'] || $r['errors'] ? 'warning' : 'success')
                        ->persistent()
                        ->send();
                }),
            Action::make('images')
                ->label('Tải ảnh hàng loạt')
                ->icon('heroicon-o-photo')
                ->color('gray')
                ->modalHeading('Tải ảnh chân dung hàng loạt')
                ->modalDescription('Đặt tên file ảnh theo Mã số của người đó (ví dụ 4205823543.jpg). Chọn nhiều ảnh cùng lúc hoặc một file .zip chứa các ảnh. Chấp nhận jpg, png, webp, gif, tối đa 8MB mỗi ảnh.')
                ->modalSubmitActionLabel('Tải lên')
                ->schema([
                    FileUpload::make('files')->label('Ảnh hoặc file .zip')->required()
                        ->multiple()->maxFiles(300)->preserveFilenames()
                        ->disk('local')->directory('imports')->visibility('private')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/zip', 'application/x-zip-compressed']),
                    Toggle::make('overwrite')->label('Ghi đè ảnh đã có')
                        ->helperText('Tắt: người đã có ảnh sẽ được giữ nguyên ảnh cũ. Bật: thay bằng ảnh mới (file ảnh cũ vẫn còn trên server).'),
                ])
                ->action(function (array $data) {
                    $importer = new OfficialImageImporter((bool) ($data['overwrite'] ?? false));

                    foreach ((array) $data['files'] as $stored) {
                        try {
                            $importer->import(Storage::disk('local')->path($stored), basename($stored));
                        } finally {
                            Storage::disk('local')->delete($stored);
                        }
                    }

                    $r = $importer->result();
                    $body = "Gán mới: {$r['attached']} · Thay thế: {$r['replaced']} · Giữ ảnh cũ: {$r['kept']} · Không có mã khớp: ".count($r['not_found']).' · Lỗi: '.count($r['invalid']);
                    foreach (['not_found' => 'Không tìm thấy Mã số', 'invalid' => 'Không hợp lệ'] as $key => $label) {
                        if ($r[$key]) {
                            $body .= "<br>$label: ".e(implode(', ', array_slice($r[$key], 0, 15))).(count($r[$key]) > 15 ? ' …' : '');
                        }
                    }

                    Notification::make()
                        ->title('Tải ảnh xong')
                        ->body(new HtmlString($body))
                        ->status($r['not_found'] || $r['invalid'] ? 'warning' : 'success')
                        ->persistent()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }

    public function getSubheading(): ?string
    {
        $total = Official::count();
        $shown = Official::where('is_active', true)->count();

        return number_format($total).' nhân sự · '.number_format($shown).' đang hiển thị · '.Part::where('is_active', true)->count().' tỉnh/thành';
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tất cả')->badge(Official::count()),
            'hidden' => Tab::make('Đang ẩn')->badge(Official::where('is_active', false)->count())->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)),
            'noimage' => Tab::make('Thiếu ảnh')->badge(Official::where('image', '')->count())->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('image', '')),
            'dupcode' => Tab::make('Trùng Mã số')->badge($dups = Official::where('member_code', '!=', '')->whereIn('member_code', fn ($q) => $q->select('member_code')->from('officials')->where('member_code', '!=', '')->groupBy('member_code')->havingRaw('count(*) > 1'))->count())->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('member_code', '!=', '')->whereIn('member_code', fn ($q) => $q->select('member_code')->from('officials')->where('member_code', '!=', '')->groupBy('member_code')->havingRaw('count(*) > 1'))),
            'nopart' => Tab::make('Chưa có đơn vị')->badge(Official::whereNull('part_id')->count())->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('part_id')),
        ];
    }
}
