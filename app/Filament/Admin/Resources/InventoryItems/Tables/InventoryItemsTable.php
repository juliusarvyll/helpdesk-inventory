<?php

namespace App\Filament\Admin\Resources\InventoryItems\Tables;

use App\Enums\InventoryItemStatus;
use App\Enums\InventoryItemType;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class InventoryItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('asset_tag')
                    ->label(__('filament-service-desk::service-desk.fields.asset_tag'))
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament-service-desk::service-desk.fields.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->label(__('filament-service-desk::service-desk.fields.department'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->label(__('filament-service-desk::service-desk.fields.category'))
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('filament-service-desk::service-desk.fields.type'))
                    ->badge()
                    ->formatStateUsing(fn (InventoryItemType $state) => $state->label())
                    ->color(fn (InventoryItemType $state) => match ($state) {
                        InventoryItemType::Asset => 'info',
                        InventoryItemType::Stock => 'success',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('filament-service-desk::service-desk.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (InventoryItemStatus $state) => $state->label())
                    ->color(fn (InventoryItemStatus $state) => match ($state) {
                        InventoryItemStatus::Available => 'success',
                        InventoryItemStatus::InRepair => 'warning',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('serial_number')
                    ->label(__('filament-service-desk::service-desk.fields.serial_number'))
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label(__('filament-service-desk::service-desk.fields.quantity'))
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state, $record) => $record->type === InventoryItemType::Stock
                        ? trim(($state ?? '—').' '.($record->unit ?? ''))
                        : '—'),
                Tables\Columns\TextColumn::make('location')
                    ->label(__('filament-service-desk::service-desk.fields.location'))
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament-service-desk::service-desk.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('department_id')
                    ->label(__('filament-service-desk::service-desk.fields.department'))
                    ->relationship('department', 'name'),
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('filament-service-desk::service-desk.fields.type'))
                    ->options(collect(InventoryItemType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('filament-service-desk::service-desk.fields.status'))
                    ->options(collect(InventoryItemStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
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
