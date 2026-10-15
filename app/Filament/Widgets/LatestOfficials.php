<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Officials\OfficialResource;
use App\Models\Official;
use App\Support\Avatar;
use Filament\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOfficials extends TableWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Nhân sự mới nhập';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => Official::query()->with('part')->orderByDesc('id'))
            ->paginated(false)
            ->defaultSort('id', 'desc')
            ->columns([
                ImageColumn::make('image')->label('')
                    ->disk('officials')->circular()->size(32)
                    ->defaultImageUrl(fn (Official $o) => Avatar::initials($o->dharma_name ?: $o->fullname)),
                TextColumn::make('fullname')->label('Họ tên / Pháp danh')
                    ->weight('semibold')
                    ->description(fn (Official $o) => $o->dharma_name),
                TextColumn::make('part.title')->label('Tỉnh/Thành')->badge()->color('success'),
                TextColumn::make('position')->label('Chức vụ')->limit(40)->tooltip(fn (Official $o) => $o->position),
                TextColumn::make('created_at')->label('Nhập lúc')->since()->color('gray'),
            ])
            ->modifyQueryUsing(fn ($query) => $query->limit(8))
            ->recordUrl(fn (Official $o) => OfficialResource::getUrl('edit', ['record' => $o]))
            ->headerActions([
                Action::make('all')->label('Xem tất cả')->url(OfficialResource::getUrl('index'))->link(),
            ]);
    }
}
