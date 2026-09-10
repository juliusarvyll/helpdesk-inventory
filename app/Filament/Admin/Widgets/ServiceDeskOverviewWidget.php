<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ServiceDeskOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -2;

    protected function getStats(): array
    {
        return [
            Stat::make(
                __('filament-service-desk::service-desk.widgets.overview.open_tickets'),
                Ticket::where('status', TicketStatus::Open)->count()
            )
                ->icon(Heroicon::Ticket)
                ->color('info'),
            Stat::make(
                __('filament-service-desk::service-desk.widgets.overview.in_progress'),
                Ticket::where('status', TicketStatus::InProgress)->count()
            )
                ->icon(Heroicon::ArrowPath)
                ->color('primary'),
            Stat::make(
                __('filament-service-desk::service-desk.widgets.overview.unassigned'),
                Ticket::whereNull('assigned_to_id')
                    ->whereNotIn('status', [TicketStatus::Closed->value, TicketStatus::Resolved->value])
                    ->count()
            )
                ->icon(Heroicon::UserMinus)
                ->color('warning'),
            Stat::make(
                __('filament-service-desk::service-desk.widgets.overview.resolved_today'),
                Ticket::where('status', TicketStatus::Resolved)
                    ->whereDate('resolved_at', today())
                    ->count()
            )
                ->icon(Heroicon::CheckCircle)
                ->color('success'),
        ];
    }
}
