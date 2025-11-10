<?php

namespace App\Filament\Resources\TripDiaries\Pages;

use App\Filament\Resources\TripDiaries\TripDiaryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTripDiary extends ViewRecord
{
    protected static string $resource = TripDiaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
