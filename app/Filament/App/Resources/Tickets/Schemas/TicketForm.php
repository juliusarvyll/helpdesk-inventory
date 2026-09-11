<?php

namespace App\Filament\App\Resources\Tickets\Schemas;

use App\Enums\TicketPriority;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;

use function filled;

class TicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(null)
            ->schema([
                Schemas\Components\Section::make(__('filament-service-desk::service-desk.sections.new_ticket'))
                    ->schema([
                        Forms\Components\Select::make('department_id')
                            ->label(__('filament-service-desk::service-desk.fields.department'))
                            ->relationship('department', 'name', fn ($query) => $query->where('is_active', true))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Schemas\Components\Utilities\Set $set) => $set('category_id', null)),
                        Forms\Components\Select::make('category_id')
                            ->label(__('filament-service-desk::service-desk.fields.category'))
                            ->relationship(
                                'category',
                                'name',
                                fn ($query, Schemas\Components\Utilities\Get $get) => $query
                                    ->where('department_id', $get('department_id'))
                                    ->where('is_active', true)
                            )
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\TextInput::make('title')
                            ->label(__('filament-service-desk::service-desk.fields.title'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('description')
                            ->label(__('filament-service-desk::service-desk.fields.description'))
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Select::make('priority')
                            ->label(__('filament-service-desk::service-desk.fields.priority'))
                            ->options(collect(TicketPriority::cases())->mapWithKeys(fn ($p) => [$p->value => $p->label()]))
                            ->default(TicketPriority::Medium->value)
                            ->required(),
                        Forms\Components\Repeater::make('ticketInventoryItems')
                            ->relationship()
                            ->label(__('filament-service-desk::service-desk.fields.inventory_items'))
                            ->defaultItems(0)
                            ->schema([
                                Forms\Components\Select::make('inventory_item_id')
                                    ->label(__('filament-service-desk::service-desk.fields.inventory_item'))
                                    ->relationship(
                                        'inventoryItem',
                                        'name',
                                        fn ($query, Schemas\Components\Utilities\Get $get) => $query
                                            ->where('department_id', $get('../../department_id'))
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Forms\Components\TextInput::make('quantity')
                                    ->label(__('filament-service-desk::service-desk.fields.quantity'))
                                    ->numeric()
                                    ->minValue(0),
                            ])
                            ->columns(2)
                            ->addActionLabel(__('filament-service-desk::service-desk.actions.add_inventory_item'))
                            ->visible(fn (Schemas\Components\Utilities\Get $get) => filled($get('department_id')))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
