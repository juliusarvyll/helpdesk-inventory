<?php

namespace App\Filament\Agent\Resources\Tickets\Pages;

use App\Filament\Agent\Resources\Tickets\TicketResource;
use Filament\Resources\Pages\ListRecords;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;
}
