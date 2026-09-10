<?php

namespace App\Filament\Admin\Resources\BusinessHoursSchedules\Pages;

use App\Filament\Admin\Resources\BusinessHoursSchedules\BusinessHoursScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBusinessHoursSchedule extends EditRecord
{
    protected static string $resource = BusinessHoursScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
