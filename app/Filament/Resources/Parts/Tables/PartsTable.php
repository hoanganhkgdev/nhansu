<?php

namespace App\Filament\Resources\Parts\Tables;

use App\Filament\Resources\Parts\PartResource;
use App\Models\Part;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class PartsTable
{
    public static function configure(Table $table): Table
    {
        $max = fn () => (int) Part::withCount('officials')->get()->max('officials_count');

        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('title')->label('Tên')->weight('semibold')
                    ->description(fn (Part $p) => $p->slug)
                    ->searchable(['title', 'slug'])->sortable(),
                ViewColumn::make('officials_count')->label('Số nhân sự')
                    ->view('filament.tables.count-bar')->viewData(['max' => $max()])->sortable(),
                TextColumn::make('note')->label('Ghi chú')->limit(60)->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('is_active')->label('Hiển thị'),
            ])
            ->recordUrl(fn (Part $p) => PartResource::getUrl('edit', ['record' => $p]))
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->disabled(fn (Part $p) => $p->officials_count > 0)
                    ->tooltip(fn (Part $p) => $p->officials_count > 0 ? 'Còn '.number_format($p->officials_count).' nhân sự, không thể xóa' : null),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->before(function (DeleteBulkAction $action, Collection $records) {
                        $used = $records->filter(fn ($p) => $p->officials_count > 0);
                        if ($used->isNotEmpty()) {
                            Notification::make()->danger()->title('Không thể xóa')
                                ->body('Còn nhân sự ở: '.$used->pluck('title')->implode(', '))->send();
                            $action->halt();
                        }
                    }),
                ]),
            ]);
    }
}
