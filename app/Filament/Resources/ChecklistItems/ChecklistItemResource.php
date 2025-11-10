<?php

namespace App\Filament\Resources\ChecklistItems;

use App\Filament\Resources\ChecklistItems\Pages\CreateChecklistItem;
use App\Filament\Resources\ChecklistItems\Pages\EditChecklistItem;
use App\Filament\Resources\ChecklistItems\Pages\ListChecklistItems;
use App\Filament\Resources\ChecklistItems\Pages\ViewChecklistItem;
use App\Filament\Resources\ChecklistItems\Schemas\ChecklistItemForm;
use App\Filament\Resources\ChecklistItems\Schemas\ChecklistItemInfolist;
use App\Filament\Resources\ChecklistItems\Tables\ChecklistItemsTable;
use App\Models\ChecklistItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ChecklistItemResource extends Resource
{
    protected static ?string $model = ChecklistItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'ChecklistItem';

    public static function form(Schema $schema): Schema
    {
        return ChecklistItemForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ChecklistItemInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChecklistItemsTable::configure($table);
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
            'index' => ListChecklistItems::route('/'),
            'create' => CreateChecklistItem::route('/create'),
            'view' => ViewChecklistItem::route('/{record}'),
            'edit' => EditChecklistItem::route('/{record}/edit'),
        ];
    }
}
