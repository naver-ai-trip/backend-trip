<?php

namespace App\Filament\Resources\CheckpointImages\Pages;

use App\Filament\Resources\CheckpointImages\CheckpointImageResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCheckpointImage extends ViewRecord
{
    protected static string $resource = CheckpointImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
