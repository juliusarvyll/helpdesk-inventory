<?php

namespace App\Filament\Admin\Resources\Tickets\Pages;

use App\Filament\Admin\Resources\Tickets\TicketResource;
use App\Services\TicketService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(TicketService::class)->update($record, $data, auth()->guard()->user());
    }
}
