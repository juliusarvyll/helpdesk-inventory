<?php

namespace App\Filament\Admin\Resources\CannedResponses\Pages;

use App\Filament\Admin\Resources\CannedResponses\CannedResponseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCannedResponse extends EditRecord
{
    protected static string $resource = CannedResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
