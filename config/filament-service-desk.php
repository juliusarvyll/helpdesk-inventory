<?php

use App\Filament\Admin\Resources\BusinessHoursSchedules\BusinessHoursScheduleResource;
use App\Filament\Admin\Resources\CannedResponses\CannedResponseResource;
use App\Filament\Admin\Resources\Categories\CategoryResource;
use App\Filament\Admin\Resources\Departments\DepartmentResource;
use App\Filament\Admin\Resources\EmailChannels\EmailChannelResource;
use App\Filament\Admin\Resources\EscalationRules\EscalationRuleResource;
use App\Filament\Admin\Resources\KbArticles\KbArticleResource;
use App\Filament\Admin\Resources\KbCategories\KbCategoryResource;
use App\Filament\Admin\Resources\ServiceCategories\ServiceCategoryResource;
use App\Filament\Admin\Resources\Services\ServiceResource;
use App\Filament\Admin\Resources\SlaPolicies\SlaPolicyResource;
use App\Filament\Admin\Resources\Tags\TagResource;
use App\Filament\Admin\Resources\Tickets\TicketResource;
use App\Filament\Admin\Widgets\ServiceDeskOverviewWidget;
use App\Filament\Admin\Widgets\SlaComplianceWidget;
use App\Filament\Admin\Widgets\TicketsByDepartmentWidget;
use App\Filament\Agent\Widgets\AgentTicketStatsWidget;
use App\Filament\Agent\Widgets\SlaBreachWidget;
use App\Filament\App\Resources\InventoryItems\InventoryItemResource;
use App\Filament\App\Resources\ServiceRequests\ServiceRequestResource;
use App\Filament\App\Widgets\MyTicketsOverviewWidget;

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Configuration
    |--------------------------------------------------------------------------
    */
    'admin' => [
        'navigation_group' => 'Service Desk',
        'navigation_icon' => 'heroicon-o-lifebuoy',
        'navigation_sort' => null,
        'resources' => [
            'department' => DepartmentResource::class,
            'category' => CategoryResource::class,
            'tag' => TagResource::class,
            'canned_response' => CannedResponseResource::class,
            'ticket' => TicketResource::class,
            'sla_policy' => SlaPolicyResource::class,
            'escalation_rule' => EscalationRuleResource::class,
            'business_hours_schedule' => BusinessHoursScheduleResource::class,
            'email_channel' => EmailChannelResource::class,
            'kb_article' => KbArticleResource::class,
            'kb_category' => KbCategoryResource::class,
            'service' => ServiceResource::class,
            'service_category' => ServiceCategoryResource::class,
        ],
        'widgets' => [
            ServiceDeskOverviewWidget::class,
            SlaComplianceWidget::class,
            TicketsByDepartmentWidget::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Agent Panel Configuration
    |--------------------------------------------------------------------------
    */
    'agent' => [
        'navigation_group' => 'Service Desk',
        'navigation_icon' => 'heroicon-o-headset',
        'navigation_sort' => null,
        'resources' => [
            'ticket' => App\Filament\Agent\Resources\Tickets\TicketResource::class,
            'canned_response' => App\Filament\Agent\Resources\CannedResponses\CannedResponseResource::class,
        ],
        'widgets' => [
            AgentTicketStatsWidget::class,
            SlaBreachWidget::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Panel Configuration
    |--------------------------------------------------------------------------
    */
    'user' => [
        'navigation_group' => 'Support',
        'navigation_icon' => 'heroicon-o-ticket',
        'navigation_sort' => null,
        'resources' => [
            'ticket' => App\Filament\App\Resources\Tickets\TicketResource::class,
            'service_request' => ServiceRequestResource::class,
            'inventory_item' => InventoryItemResource::class,
        ],
        'widgets' => [
            MyTicketsOverviewWidget::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Toggle features on/off globally. These can also be toggled per-plugin
    | using fluent methods on the plugin classes.
    |
    */

    'features' => [
        'knowledge_base' => true,
        'sla' => true,
        'email_channels' => true,
        'service_catalog' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Attachments
    |--------------------------------------------------------------------------
    |
    | Configure file upload settings for ticket attachments.
    | The allowed_extensions are resolved to MIME types using Symfony MimeTypes.
    |
    */

    'attachments' => [
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'zip', 'rar'],
        'max_file_size' => 10240, // in KB (10MB)
        'disk' => 'local',
    ],

];
