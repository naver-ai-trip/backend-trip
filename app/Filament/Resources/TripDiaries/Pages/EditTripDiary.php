<?php

namespace App\Filament\Resources\TripDiaries\Pages;

use App\Filament\Resources\TripDiaries\TripDiaryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTripDiary extends EditRecord
{
    protected static string $resource = TripDiaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
