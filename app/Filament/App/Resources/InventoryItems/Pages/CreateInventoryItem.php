<?php

namespace App\Filament\App\Resources\InventoryItems\Pages;

use App\Filament\App\Resources\InventoryItems\InventoryItemResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateInventoryItem extends CreateRecord
{
    protected static string $resource = InventoryItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var User $user */
        $user = auth()->guard()->user();

        $data['department_id'] = $user->department_id;
        $data['created_by_id'] = $user->getKey();

        return $data;
    }
}
