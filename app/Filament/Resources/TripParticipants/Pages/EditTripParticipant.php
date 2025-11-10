<?php

namespace App\Filament\Resources\TripParticipants\Pages;

use App\Filament\Resources\TripParticipants\TripParticipantResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTripParticipant extends EditRecord
{
    protected static string $resource = TripParticipantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
