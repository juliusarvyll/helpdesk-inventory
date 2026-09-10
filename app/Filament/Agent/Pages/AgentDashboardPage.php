<?php

namespace App\Filament\Agent\Pages;

use App\Filament\Agent\Widgets\AgentTicketStatsWidget;
use App\Filament\Agent\Widgets\SlaBreachWidget;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class AgentDashboardPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = -1;

    protected string $view = 'filament-service-desk::pages.agent.dashboard';

    public static function getNavigationGroup(): ?string
    {
        return __('filament-service-desk::service-desk.navigation.agent.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-service-desk::service-desk.pages.agent_dashboard.label');
    }

    public function getTitle(): string
    {
        return __('filament-service-desk::service-desk.pages.agent_dashboard.title');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AgentTicketStatsWidget::class,
            SlaBreachWidget::class,
        ];
    }
}
