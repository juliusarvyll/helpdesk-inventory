<?php

namespace App\Filament\Admin\Resources\BusinessHoursSchedules\Pages;

use App\Filament\Admin\Resources\BusinessHoursSchedules\BusinessHoursScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBusinessHoursSchedules extends ListRecords
{
    protected static string $resource = BusinessHoursScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
