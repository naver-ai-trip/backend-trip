<?php

namespace App\Filament\Resources\TripDiaries;

use App\Filament\Resources\TripDiaries\Pages\CreateTripDiary;
use App\Filament\Resources\TripDiaries\Pages\EditTripDiary;
use App\Filament\Resources\TripDiaries\Pages\ListTripDiaries;
use App\Filament\Resources\TripDiaries\Pages\ViewTripDiary;
use App\Filament\Resources\TripDiaries\Schemas\TripDiaryForm;
use App\Filament\Resources\TripDiaries\Schemas\TripDiaryInfolist;
use App\Filament\Resources\TripDiaries\Tables\TripDiariesTable;
use App\Models\TripDiary;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TripDiaryResource extends Resource
{
    protected static ?string $model = TripDiary::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'TripDiary';

    public static function form(Schema $schema): Schema
    {
        return TripDiaryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TripDiaryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TripDiariesTable::configure($table);
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
            'index' => ListTripDiaries::route('/'),
            'create' => CreateTripDiary::route('/create'),
            'view' => ViewTripDiary::route('/{record}'),
            'edit' => EditTripDiary::route('/{record}/edit'),
        ];
    }
}
