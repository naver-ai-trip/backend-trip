<?php

namespace App\Filament\Resources\TripDiaries\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TripDiaryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('trip.title')
                    ->label('Trip'),
                TextEntry::make('user.name')
                    ->label('User'),
                TextEntry::make('entry_date')
                    ->date(),
                TextEntry::make('text')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('mood')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
