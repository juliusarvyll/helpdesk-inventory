<?php

namespace App\Filament\App\Resources\InventoryItems\Pages;

use App\Filament\App\Resources\InventoryItems\InventoryItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventoryItems extends ListRecords
{
    protected static string $resource = InventoryItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
