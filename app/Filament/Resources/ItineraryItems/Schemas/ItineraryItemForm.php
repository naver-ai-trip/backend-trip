<?php

namespace App\Filament\Resources\ItineraryItems\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ItineraryItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('trip_id')
                    ->relationship('trip', 'title')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                TextInput::make('day_number')
                    ->required()
                    ->numeric(),
                DateTimePicker::make('start_time'),
                DateTimePicker::make('end_time'),
                Select::make('place_id')
                    ->relationship('place', 'name'),
                Textarea::make('note')
                    ->columnSpanFull(),
            ]);
    }
}
