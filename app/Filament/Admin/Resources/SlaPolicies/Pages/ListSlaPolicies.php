<?php

namespace App\Filament\Admin\Resources\SlaPolicies\Pages;

use App\Filament\Admin\Resources\SlaPolicies\SlaPolicyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSlaPolicies extends ListRecords
{
    protected static string $resource = SlaPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
