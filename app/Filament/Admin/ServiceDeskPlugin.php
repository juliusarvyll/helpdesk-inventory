<?php

namespace App\Filament\Admin;

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
        return 'filament-service-desk-admin';
    }

    public function register(Panel $panel): void
    {
        $resources = config('filament-service-desk.admin.resources', []);

        $enabled = [
            $resources['department'] ?? Resources\Departments\DepartmentResource::class,
            $resources['category'] ?? Resources\Categories\CategoryResource::class,
            $resources['tag'] ?? Resources\Tags\TagResource::class,
            $resources['canned_response'] ?? Resources\CannedResponses\CannedResponseResource::class,
            $resources['ticket'] ?? Resources\Tickets\TicketResource::class,
            $resources['inventory_item'] ?? Resources\InventoryItems\InventoryItemResource::class,
        ];

        if ($this->hasSla()) {
            $enabled[] = $resources['sla_policy'] ?? Resources\SlaPolicies\SlaPolicyResource::class;
            $enabled[] = $resources['escalation_rule'] ?? Resources\EscalationRules\EscalationRuleResource::class;
            $enabled[] = $resources['business_hours_schedule'] ?? Resources\BusinessHoursSchedules\BusinessHoursScheduleResource::class;
        }

        if ($this->hasEmailChannels()) {
            $enabled[] = $resources['email_channel'] ?? Resources\EmailChannels\EmailChannelResource::class;
        }

        if ($this->hasKnowledgeBase()) {
            $enabled[] = $resources['kb_article'] ?? Resources\KbArticles\KbArticleResource::class;
            $enabled[] = $resources['kb_category'] ?? Resources\KbCategories\KbCategoryResource::class;
        }

        if ($this->hasServiceCatalog()) {
            $enabled[] = $resources['service'] ?? Resources\Services\ServiceResource::class;
            $enabled[] = $resources['service_category'] ?? Resources\ServiceCategories\ServiceCategoryResource::class;
            $enabled[] = $resources['service_request'] ?? Resources\ServiceRequests\ServiceRequestResource::class;
        }

        $panel
            ->resources(array_values(array_filter($enabled)))
            ->widgets(config('filament-service-desk.admin.widgets', [
                Widgets\ServiceDeskOverviewWidget::class,
                Widgets\SlaComplianceWidget::class,
                Widgets\TicketsByDepartmentWidget::class,
            ]));
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
