<?php

namespace App\Filament\Agent\Resources\ServiceRequests\Pages;

use App\Enums\ServiceRequestStatus;
use App\Filament\Agent\Resources\ServiceRequests\ServiceRequestResource;
use App\Filament\Agent\Resources\ServiceRequests\Tables\ServiceRequestsTable;
use App\Models\ServiceRequest;
use App\Services\ServiceRequestService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewServiceRequest extends ViewRecord
{
    protected static string $resource = ServiceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label(__('filament-service-desk::service-desk.actions.approve'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (ServiceRequest $record) => $record->status === ServiceRequestStatus::Pending)
                ->requiresConfirmation()
                ->action(function (ServiceRequest $record): void {
                    app(ServiceRequestService::class)->updateStatus($record, ServiceRequestStatus::Approved);

                    Notification::make()
                        ->title(__('filament-service-desk::service-desk.actions.approve'))
                        ->success()
                        ->send();
                }),
            Actions\Action::make('reject')
                ->label(__('filament-service-desk::service-desk.actions.reject'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (ServiceRequest $record) => $record->status === ServiceRequestStatus::Pending)
                ->requiresConfirmation()
                ->action(function (ServiceRequest $record): void {
                    app(ServiceRequestService::class)->updateStatus($record, ServiceRequestStatus::Rejected);

                    Notification::make()
                        ->title(__('filament-service-desk::service-desk.actions.reject'))
                        ->success()
                        ->send();
                }),
            ServiceRequestsTable::updateStatusAction(),
        ];
    }
}
