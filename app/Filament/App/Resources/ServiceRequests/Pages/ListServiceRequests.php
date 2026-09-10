<?php

namespace App\Filament\App\Resources\ServiceRequests\Pages;

use App\Filament\App\Resources\ServiceRequests\ServiceRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServiceRequests extends ListRecords
{
    protected static string $resource = ServiceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
