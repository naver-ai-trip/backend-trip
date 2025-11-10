<?php

namespace App\Filament\Resources\CheckpointImages\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CheckpointImageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('map_checkpoint_id')
                    ->required()
                    ->numeric(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('file_path')
                    ->required(),
                TextInput::make('caption'),
                TextInput::make('moderation_results'),
                Toggle::make('is_flagged')
                    ->required(),
                DateTimePicker::make('uploaded_at')
                    ->required(),
            ]);
    }
}
