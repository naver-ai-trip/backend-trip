<?php

namespace App\Filament\Resources\ItineraryItems\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ItineraryItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('trip.title')
                    ->label('Trip'),
                TextEntry::make('title'),
                TextEntry::make('day_number')
                    ->numeric(),
                TextEntry::make('start_time')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('end_time')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('place.name')
                    ->label('Place')
                    ->placeholder('-'),
                TextEntry::make('note')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
