<?php

namespace App\Filament\App;

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
        return 'filament-service-desk-user';
    }

    public function register(Panel $panel): void
    {
        $resources = config('filament-service-desk.user.resources', []);

        $enabled = [
            $resources['ticket'] ?? Resources\Tickets\TicketResource::class,
            $resources['inventory_item'] ?? Resources\InventoryItems\InventoryItemResource::class,
        ];

        $pages = [];

        if ($this->hasServiceCatalog()) {
            $enabled[] = $resources['service_request'] ?? Resources\ServiceRequests\ServiceRequestResource::class;
        }

        if ($this->hasKnowledgeBase()) {
            $pages[] = Pages\KnowledgeBasePage::class;
        }

        $panel
            ->resources(array_values(array_filter($enabled)))
            ->pages($pages)
            ->widgets(config('filament-service-desk.user.widgets', [
                Widgets\MyTicketsOverviewWidget::class,
            ]));
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
