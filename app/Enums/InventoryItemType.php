<?php

namespace App\Enums;

enum InventoryItemType: string
{
    case Asset = 'asset';
    case Stock = 'stock';

    public function label(): string
    {
        return __('service-desk::service-desk.inventory_item_type.'.$this->value);
    }
}
