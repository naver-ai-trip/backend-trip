<?php

namespace App\Filament\Resources\TripParticipants;

use App\Filament\Resources\TripParticipants\Pages\CreateTripParticipant;
use App\Filament\Resources\TripParticipants\Pages\EditTripParticipant;
use App\Filament\Resources\TripParticipants\Pages\ListTripParticipants;
use App\Filament\Resources\TripParticipants\Pages\ViewTripParticipant;
use App\Filament\Resources\TripParticipants\Schemas\TripParticipantForm;
use App\Filament\Resources\TripParticipants\Schemas\TripParticipantInfolist;
use App\Filament\Resources\TripParticipants\Tables\TripParticipantsTable;
use App\Models\TripParticipant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TripParticipantResource extends Resource
{
    protected static ?string $model = TripParticipant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'TripParticipant';

    public static function form(Schema $schema): Schema
    {
        return TripParticipantForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TripParticipantInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TripParticipantsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTripParticipants::route('/'),
            'create' => CreateTripParticipant::route('/create'),
            'view' => ViewTripParticipant::route('/{record}'),
            'edit' => EditTripParticipant::route('/{record}/edit'),
        ];
    }
}
