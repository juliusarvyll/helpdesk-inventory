<?php

namespace App\Filament\Admin\Resources\EmailChannels\Pages;

use App\Filament\Admin\Resources\EmailChannels\EmailChannelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmailChannel extends EditRecord
{
    protected static string $resource = EmailChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
