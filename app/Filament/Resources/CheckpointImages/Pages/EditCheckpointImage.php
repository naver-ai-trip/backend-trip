<?php

namespace App\Filament\Resources\CheckpointImages\Pages;

use App\Filament\Resources\CheckpointImages\CheckpointImageResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCheckpointImage extends EditRecord
{
    protected static string $resource = CheckpointImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
