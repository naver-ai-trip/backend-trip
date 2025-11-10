<?php

namespace App\Filament\Resources\Translations\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TranslationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user_id')
                    ->numeric(),
                TextEntry::make('source_type'),
                TextEntry::make('source_text')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('source_language'),
                TextEntry::make('translated_text')
                    ->columnSpanFull(),
                TextEntry::make('target_language'),
                TextEntry::make('file_path')
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
