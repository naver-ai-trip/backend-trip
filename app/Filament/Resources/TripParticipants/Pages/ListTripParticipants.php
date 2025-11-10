<?php

namespace App\Filament\Resources\TripParticipants\Pages;

use App\Filament\Resources\TripParticipants\TripParticipantResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTripParticipants extends ListRecords
{
    protected static string $resource = TripParticipantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
