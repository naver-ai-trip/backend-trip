<?php

namespace App\Filament\Resources\MapCheckpoints\Pages;

use App\Filament\Resources\MapCheckpoints\MapCheckpointResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMapCheckpoint extends ViewRecord
{
    protected static string $resource = MapCheckpointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
