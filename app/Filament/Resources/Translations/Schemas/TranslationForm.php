<?php

namespace App\Filament\Resources\Translations\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class TranslationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('source_type')
                    ->required(),
                Textarea::make('source_text')
                    ->columnSpanFull(),
                TextInput::make('source_language')
                    ->required(),
                Textarea::make('translated_text')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('target_language')
                    ->required(),
                TextInput::make('file_path'),
            ]);
    }
}
