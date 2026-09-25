<?php

namespace App\Filament\Admin\Resources\Categories\Tables;

use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament-service-desk::service-desk.fields.name'))
                    ->searchable()
                    ->sortable(query: fn (Builder $query, string $direction) => self::sortByHierarchy($query, $direction)),
                Tables\Columns\TextColumn::make('department.name')
                    ->label(__('filament-service-desk::service-desk.fields.department'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label(__('filament-service-desk::service-desk.fields.parent_category'))
                    ->placeholder('—')
                    ->sortable(query: fn (Builder $query, string $direction) => self::sortByHierarchy($query, $direction)),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('filament-service-desk::service-desk.fields.is_active'))
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('filament-service-desk::service-desk.fields.sort_order'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament-service-desk::service-desk.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort(fn (Builder $query) => self::sortByHierarchy($query, 'asc'))
            ->filters([
                Tables\Filters\SelectFilter::make('department_id')
                    ->label(__('filament-service-desk::service-desk.fields.department'))
                    ->relationship('department', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('filament-service-desk::service-desk.fields.is_active')),
                Tables\Filters\Filter::make('root_only')
                    ->label(__('filament-service-desk::service-desk.filters.root_only'))
                    ->query(fn ($query) => $query->whereNull('parent_id'))
                    ->toggle(),
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

    /**
     * Orders categories so each parent category is followed immediately by its own children,
     * with parent groups ordered by the given direction and children always ordered under
     * their parent by sort order and name.
     */
    private static function sortByHierarchy(Builder $query, string $direction): Builder
    {
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $alreadyJoined = collect($query->getQuery()->joins)
            ->contains(fn ($join) => $join->table === 'service_desk_categories as parent_categories');

        if (! $alreadyJoined) {
            $query->leftJoin('service_desk_categories as parent_categories', 'parent_categories.id', '=', 'service_desk_categories.parent_id');
        }

        return $query
            ->orderByRaw("coalesce(parent_categories.sort_order, service_desk_categories.sort_order) {$direction}")
            ->orderByRaw("coalesce(parent_categories.name, service_desk_categories.name) {$direction}")
            ->orderBy('service_desk_categories.parent_id')
            ->orderBy('service_desk_categories.sort_order')
            ->orderBy('service_desk_categories.name')
            ->select('service_desk_categories.*');
    }
}
