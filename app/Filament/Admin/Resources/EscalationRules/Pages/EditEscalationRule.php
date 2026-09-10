<?php

namespace App\Filament\Admin\Resources\EscalationRules\Pages;

use App\Filament\Admin\Resources\EscalationRules\EscalationRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEscalationRule extends EditRecord
{
    protected static string $resource = EscalationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
