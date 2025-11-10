<?php

namespace App\Filament\Resources\MapCheckpoints\Pages;

use App\Filament\Resources\MapCheckpoints\MapCheckpointResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMapCheckpoint extends EditRecord
{
    protected static string $resource = MapCheckpointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
