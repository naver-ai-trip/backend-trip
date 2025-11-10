<?php

namespace App\Filament\Resources\CheckpointImages;

use App\Filament\Resources\CheckpointImages\Pages\CreateCheckpointImage;
use App\Filament\Resources\CheckpointImages\Pages\EditCheckpointImage;
use App\Filament\Resources\CheckpointImages\Pages\ListCheckpointImages;
use App\Filament\Resources\CheckpointImages\Pages\ViewCheckpointImage;
use App\Filament\Resources\CheckpointImages\Schemas\CheckpointImageForm;
use App\Filament\Resources\CheckpointImages\Schemas\CheckpointImageInfolist;
use App\Filament\Resources\CheckpointImages\Tables\CheckpointImagesTable;
use App\Models\CheckpointImage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CheckpointImageResource extends Resource
{
    protected static ?string $model = CheckpointImage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'CheckpointImage';

    public static function form(Schema $schema): Schema
    {
        return CheckpointImageForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CheckpointImageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CheckpointImagesTable::configure($table);
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
            'index' => ListCheckpointImages::route('/'),
            'create' => CreateCheckpointImage::route('/create'),
            'view' => ViewCheckpointImage::route('/{record}'),
            'edit' => EditCheckpointImage::route('/{record}/edit'),
        ];
    }
}
