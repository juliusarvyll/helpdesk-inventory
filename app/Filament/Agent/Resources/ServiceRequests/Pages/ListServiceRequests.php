<?php

namespace App\Filament\Agent\Resources\ServiceRequests\Pages;

use App\Filament\Agent\Resources\ServiceRequests\ServiceRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListServiceRequests extends ListRecords
{
    protected static string $resource = ServiceRequestResource::class;
}
