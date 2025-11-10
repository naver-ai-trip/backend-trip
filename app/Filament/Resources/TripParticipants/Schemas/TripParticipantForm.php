<?php

namespace App\Filament\Resources\TripParticipants\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TripParticipantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('trip_id')
                    ->required()
                    ->numeric(),
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('role')
                    ->required()
                    ->default('viewer'),
                DateTimePicker::make('joined_at')
                    ->required(),
            ]);
    }
}
