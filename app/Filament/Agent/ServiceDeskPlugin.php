<?php

namespace App\Filament\Agent;

use App\Filament\Concerns\HasServiceDeskPluginConfig;
use Filament\Contracts\Plugin;
use Filament\Panel;

class ServiceDeskPlugin implements Plugin
{
    use HasServiceDeskPluginConfig;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function getId(): string
    {
        return 'filament-service-desk-agent';
    }

    public function register(Panel $panel): void
    {
        $resources = config('filament-service-desk.agent.resources', []);

        $enabled = [
            $resources['ticket'] ?? Resources\Tickets\TicketResource::class,
            $resources['canned_response'] ?? Resources\CannedResponses\CannedResponseResource::class,
        ];

        $pages = [
            Pages\TicketQueuePage::class,
            Pages\AgentDashboardPage::class,
        ];

        $panel
            ->resources(array_values(array_filter($enabled)))
            ->pages($pages)
            ->widgets(config('filament-service-desk.agent.widgets', [
                Widgets\AgentTicketStatsWidget::class,
                Widgets\SlaBreachWidget::class,
            ]));
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
