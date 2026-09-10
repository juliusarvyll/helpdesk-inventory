<?php

namespace App\Filament\Admin\Resources\KbCategories;

use App\Filament\Admin\Resources\KbCategories\Schemas\KbCategoryForm;
use App\Filament\Admin\Resources\KbCategories\Tables\KbCategoriesTable;
use App\Models\KbCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class KbCategoryResource extends Resource
{
    protected static ?string $model = KbCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static ?int $navigationSort = 31;

    public static function getNavigationGroup(): ?string
    {
        return __('filament-service-desk::service-desk.navigation.admin.kb_group');
    }

    public static function getModelLabel(): string
    {
        return __('filament-service-desk::service-desk.resources.kb_category.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-service-desk::service-desk.resources.kb_category.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return KbCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KbCategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKbCategories::route('/'),
            'create' => Pages\CreateKbCategory::route('/create'),
            'edit' => Pages\EditKbCategory::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return config('filament-service-desk.admin.resources.kb_category') !== null;
    }
}
