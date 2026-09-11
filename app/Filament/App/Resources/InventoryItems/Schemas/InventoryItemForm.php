<?php

namespace App\Filament\App\Resources\InventoryItems\Schemas;

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
                    ->native(false),
                Forms\Components\TextInput::make('name')
                    ->label(__('filament-service-desk::service-desk.fields.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label(__('filament-service-desk::service-desk.fields.description'))
                    ->columnSpanFull(),
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
            ]);
    }
}
