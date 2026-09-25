<?php

namespace App\Filament\Agent\Resources\Tickets\Pages;

use App\Filament\Agent\Resources\Tickets\Actions\CloseTicketAction;
use App\Filament\Agent\Resources\Tickets\TicketResource;
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
            CloseTicketAction::make()
                ->after(fn () => $this->redirect(TicketResource::getUrl('view', ['record' => $this->getRecord()]))),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(TicketService::class)->update($record, $data, auth()->guard()->user());
    }
}
