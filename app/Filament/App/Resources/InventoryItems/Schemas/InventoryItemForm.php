<?php

namespace App\Filament\App\Resources\InventoryItems\Schemas;

use App\Enums\InventoryItemStatus;
use App\Enums\InventoryItemType;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;

class InventoryItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Forms\Components\Select::make('type')
                    ->label(__('filament-service-desk::service-desk.fields.type'))
                    ->options(collect(InventoryItemType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()]))
                    ->required()
                    ->live()
                    ->native(false)
                    ->afterStateUpdated(function (Schemas\Components\Utilities\Set $set, ?string $state) {
                        if ($state === InventoryItemType::Asset->value) {
                            $set('quantity', null);
                            $set('unit', null);
                        } elseif ($state === InventoryItemType::Stock->value) {
                            $set('serial_number', null);
                        }
                    }),
                Forms\Components\Select::make('status')
                    ->label(__('filament-service-desk::service-desk.fields.status'))
                    ->options(collect(InventoryItemStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                    ->default(InventoryItemStatus::Available->value)
                    ->required()
                    ->native(false),
                Forms\Components\TextInput::make('name')
                    ->label(__('filament-service-desk::service-desk.fields.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('category')
                    ->label(__('filament-service-desk::service-desk.fields.category'))
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label(__('filament-service-desk::service-desk.fields.description'))
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('asset_tag')
                    ->label(__('filament-service-desk::service-desk.fields.asset_tag'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('serial_number')
                    ->label(__('filament-service-desk::service-desk.fields.serial_number'))
                    ->maxLength(255)
                    ->visible(fn (Schemas\Components\Utilities\Get $get) => $get('type') === InventoryItemType::Asset->value),
                Forms\Components\TextInput::make('quantity')
                    ->label(__('filament-service-desk::service-desk.fields.quantity'))
                    ->numeric()
                    ->minValue(0)
                    ->visible(fn (Schemas\Components\Utilities\Get $get) => $get('type') === InventoryItemType::Stock->value),
                Forms\Components\TextInput::make('unit')
                    ->label(__('filament-service-desk::service-desk.fields.unit'))
                    ->maxLength(255)
                    ->visible(fn (Schemas\Components\Utilities\Get $get) => $get('type') === InventoryItemType::Stock->value),
                Forms\Components\TextInput::make('location')
                    ->label(__('filament-service-desk::service-desk.fields.location'))
                    ->maxLength(255),
                Forms\Components\DatePicker::make('purchased_at')
                    ->label(__('filament-service-desk::service-desk.fields.purchased_at')),
                Forms\Components\DatePicker::make('warranty_expires_at')
                    ->label(__('filament-service-desk::service-desk.fields.warranty_expires_at')),
            ]);
    }
}
