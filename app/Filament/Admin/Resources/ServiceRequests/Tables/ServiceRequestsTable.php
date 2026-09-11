<?php

namespace App\Filament\Admin\Resources\ServiceRequests\Tables;

use App\Enums\ServiceRequestStatus;
use App\Models\ServiceRequest;
use App\Services\ServiceRequestService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('service.name')
                    ->label(__('filament-service-desk::service-desk.fields.service'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('requester.name')
                    ->label(__('filament-service-desk::service-desk.fields.requester'))
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('ticket.reference_number')
                    ->label(__('filament-service-desk::service-desk.fields.ticket'))
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('filament-service-desk::service-desk.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (ServiceRequestStatus $state) => $state->label())
                    ->color(fn (ServiceRequestStatus $state) => match ($state) {
                        ServiceRequestStatus::Pending => 'warning',
                        ServiceRequestStatus::Approved => 'info',
                        ServiceRequestStatus::Rejected => 'danger',
                        ServiceRequestStatus::InProgress => 'primary',
                        ServiceRequestStatus::Fulfilled => 'success',
                        ServiceRequestStatus::Cancelled => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament-service-desk::service-desk.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('filament-service-desk::service-desk.fields.status'))
                    ->options(collect(ServiceRequestStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                self::updateStatusAction(),
            ]);
    }

    public static function updateStatusAction(): Actions\Action
    {
        return Actions\Action::make('updateStatus')
            ->label(__('filament-service-desk::service-desk.actions.update_status'))
            ->icon('heroicon-o-arrow-path')
            ->schema([
                Forms\Components\Select::make('status')
                    ->label(__('filament-service-desk::service-desk.fields.status'))
                    ->options(collect(ServiceRequestStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                    ->default(fn (ServiceRequest $record) => $record->status->value)
                    ->required()
                    ->native(false),
            ])
            ->action(function (ServiceRequest $record, array $data): void {
                app(ServiceRequestService::class)->updateStatus($record, ServiceRequestStatus::from($data['status']));

                Notification::make()
                    ->title(__('filament-service-desk::service-desk.actions.update_status'))
                    ->success()
                    ->send();
            });
    }
}
