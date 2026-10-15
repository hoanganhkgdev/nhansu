<?php

namespace App\Filament\Resources\Officials\Pages;

use App\Filament\Resources\Officials\OfficialResource;
use App\Support\OfficialQr;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOfficial extends EditRecord
{
    protected static string $resource = OfficialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('qr')->label('Tải mã QR')->icon('heroicon-o-qr-code')->color('gray')
                ->action(fn () => response()->streamDownload(fn () => print (OfficialQr::png($this->getRecord())), OfficialQr::filename($this->getRecord()), ['Content-Type' => 'image/png'])),
            DeleteAction::make(),
        ];
    }
}
