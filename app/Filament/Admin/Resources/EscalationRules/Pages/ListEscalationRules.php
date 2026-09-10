<?php

namespace App\Filament\Admin\Resources\EscalationRules\Pages;

use App\Filament\Admin\Resources\EscalationRules\EscalationRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEscalationRules extends ListRecords
{
    protected static string $resource = EscalationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
