<?php

namespace App\Filament\App\Resources\InventoryItems;

use App\Filament\App\Resources\InventoryItems\Schemas\InventoryItemForm;
use App\Filament\App\Resources\InventoryItems\Tables\InventoryItemsTable;
use App\Models\InventoryItem;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryItemResource extends Resource
{
    protected static ?string $model = InventoryItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('filament-service-desk::service-desk.navigation.user.group');
    }

    public static function getModelLabel(): string
    {
        return __('filament-service-desk::service-desk.resources.inventory_item.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-service-desk::service-desk.resources.inventory_item.plural_label');
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var User $user */
        $user = auth()->guard()->user();

        // department_id is NOT NULL at the DB level, so when $user->department_id is null this
        // compiles to `WHERE department_id IS NULL`, which correctly matches zero rows. If the
        // column is ever made nullable, this scoping would start leaking department-less items.
        return parent::getEloquentQuery()
            ->where('department_id', $user->department_id);
    }

    public static function canCreate(): bool
    {
        /** @var User $user */
        $user = auth()->guard()->user();

        return $user->department_id !== null;
    }

    public static function form(Schema $schema): Schema
    {
        return InventoryItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InventoryItemsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryItems::route('/'),
            'create' => Pages\CreateInventoryItem::route('/create'),
            'edit' => Pages\EditInventoryItem::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return config('filament-service-desk.user.resources.inventory_item') !== null;
    }
}
