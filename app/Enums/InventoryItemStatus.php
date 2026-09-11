<?php

namespace App\Enums;

enum InventoryItemStatus: string
{
    case Available = 'available';
    case InRepair = 'in_repair';

    public function label(): string
    {
        return __('service-desk::service-desk.inventory_item_status.'.$this->value);
    }
}
