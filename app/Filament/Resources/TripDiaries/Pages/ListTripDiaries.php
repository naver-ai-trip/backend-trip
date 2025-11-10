<?php

namespace App\Filament\Resources\TripDiaries\Pages;

use App\Filament\Resources\TripDiaries\TripDiaryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTripDiaries extends ListRecords
{
    protected static string $resource = TripDiaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
