<?php

namespace App\Filament\App\Resources\InventoryItems\Tables;

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
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament-service-desk::service-desk.fields.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('filament-service-desk::service-desk.fields.type'))
                    ->badge()
                    ->formatStateUsing(fn (InventoryItemType $state) => $state->label())
                    ->color(fn (InventoryItemType $state) => match ($state) {
                        InventoryItemType::Asset => 'info',
                        InventoryItemType::Stock => 'success',
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
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label(__('filament-service-desk::service-desk.fields.created_by'))
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament-service-desk::service-desk.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('filament-service-desk::service-desk.fields.type'))
                    ->options(collect(InventoryItemType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->emptyStateHeading(fn () => auth()->guard()->user()?->department_id
                ? __('filament-service-desk::service-desk.messages.no_inventory_items')
                : __('filament-service-desk::service-desk.messages.no_department_assigned'));
    }
}
