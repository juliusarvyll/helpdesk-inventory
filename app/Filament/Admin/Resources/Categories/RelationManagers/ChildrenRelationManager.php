<?php

namespace App\Filament\Admin\Resources\Categories\RelationManagers;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('filament-service-desk::service-desk.relations.subcategories');
    }

    /**
     * Categories are only two levels deep, so subcategories are only managed on parent categories.
     */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->parent_id === null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('filament-service-desk::service-desk.fields.name'))
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\Textarea::make('description')
                    ->label(__('filament-service-desk::service-desk.fields.description'))
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('filament-service-desk::service-desk.fields.is_active'))
                    ->default(true),
                Forms\Components\TextInput::make('sort_order')
                    ->label(__('filament-service-desk::service-desk.fields.sort_order'))
                    ->numeric()
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament-service-desk::service-desk.fields.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('filament-service-desk::service-desk.fields.is_active'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('filament-service-desk::service-desk.fields.sort_order'))
                    ->sortable(),
            ])
            ->defaultSort(fn ($query) => $query->orderBy('sort_order')->orderBy('name'))
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('filament-service-desk::service-desk.fields.is_active')),
            ])
            ->headerActions([
                Actions\CreateAction::make(),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
