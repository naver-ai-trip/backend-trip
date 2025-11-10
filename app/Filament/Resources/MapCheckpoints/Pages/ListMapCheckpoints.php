<?php

namespace App\Filament\Resources\MapCheckpoints\Pages;

use App\Filament\Resources\MapCheckpoints\MapCheckpointResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMapCheckpoints extends ListRecords
{
    protected static string $resource = MapCheckpointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
