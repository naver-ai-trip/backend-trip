<?php

namespace App\Filament\Resources\Shares\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ShareInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('trip.title')
                    ->label('Trip'),
                TextEntry::make('user.name')
                    ->label('User'),
                TextEntry::make('permission'),
                TextEntry::make('token'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
