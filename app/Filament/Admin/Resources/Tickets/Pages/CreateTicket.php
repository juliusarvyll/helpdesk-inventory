<?php

namespace App\Filament\Admin\Resources\Tickets\Pages;

use App\Filament\Admin\Resources\Tickets\TicketResource;
use App\Services\TicketService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(TicketService::class)->create($data, auth()->guard()->user());
    }
}
