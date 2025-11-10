<?php

namespace App\Filament\Resources\TripParticipants\Pages;

use App\Filament\Resources\TripParticipants\TripParticipantResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTripParticipant extends ViewRecord
{
    protected static string $resource = TripParticipantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
