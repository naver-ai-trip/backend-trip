<?php

namespace App\Filament\Resources\ItineraryItems\Pages;

use App\Filament\Resources\ItineraryItems\ItineraryItemResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewItineraryItem extends ViewRecord
{
    protected static string $resource = ItineraryItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
