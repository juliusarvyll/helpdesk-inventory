<?php

namespace App\Filament\Admin\Resources\KbArticles;

use App\Filament\Admin\Resources\KbArticles\Schemas\KbArticleForm;
use App\Filament\Admin\Resources\KbArticles\Tables\KbArticlesTable;
use App\Models\KbArticle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class KbArticleResource extends Resource
{
    protected static ?string $model = KbArticle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 30;

    public static function getNavigationGroup(): ?string
    {
        return __('filament-service-desk::service-desk.navigation.admin.kb_group');
    }

    public static function getModelLabel(): string
    {
        return __('filament-service-desk::service-desk.resources.kb_article.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-service-desk::service-desk.resources.kb_article.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return KbArticleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KbArticlesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\VersionsRelationManager::class,
            RelationManagers\FeedbackRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKbArticles::route('/'),
            'create' => Pages\CreateKbArticle::route('/create'),
            'edit' => Pages\EditKbArticle::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return config('filament-service-desk.admin.resources.kb_article') !== null;
    }
}
