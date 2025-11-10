<?php

namespace App\Filament\Resources\CheckpointImages\Pages;

use App\Filament\Resources\CheckpointImages\CheckpointImageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCheckpointImages extends ListRecords
{
    protected static string $resource = CheckpointImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
