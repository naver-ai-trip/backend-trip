<?php

namespace App\Filament\Resources\MapCheckpoints;

use App\Filament\Resources\MapCheckpoints\Pages\CreateMapCheckpoint;
use App\Filament\Resources\MapCheckpoints\Pages\EditMapCheckpoint;
use App\Filament\Resources\MapCheckpoints\Pages\ListMapCheckpoints;
use App\Filament\Resources\MapCheckpoints\Pages\ViewMapCheckpoint;
use App\Filament\Resources\MapCheckpoints\Schemas\MapCheckpointForm;
use App\Filament\Resources\MapCheckpoints\Schemas\MapCheckpointInfolist;
use App\Filament\Resources\MapCheckpoints\Tables\MapCheckpointsTable;
use App\Models\MapCheckpoint;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MapCheckpointResource extends Resource
{
    protected static ?string $model = MapCheckpoint::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'MapCheckpoint';

    public static function form(Schema $schema): Schema
    {
        return MapCheckpointForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MapCheckpointInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MapCheckpointsTable::configure($table);
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
            'index' => ListMapCheckpoints::route('/'),
            'create' => CreateMapCheckpoint::route('/create'),
            'view' => ViewMapCheckpoint::route('/{record}'),
            'edit' => EditMapCheckpoint::route('/{record}/edit'),
        ];
    }
}
