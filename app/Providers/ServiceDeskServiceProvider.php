<?php

namespace App\Providers;

use App\Console\Commands\CheckSlaBreachesCommand;
use App\Console\Commands\CleanInboundEmailsCommand;
use App\Console\Commands\CloseStaleTicketsCommand;
use App\Console\Commands\PollImapMailboxCommand;
use App\Console\Commands\ProcessEscalationsCommand;
use App\Console\Commands\RecalculateSlaCommand;
use App\Contracts\SlaCalculator;
use App\Events\CommentAdded;
use App\Events\InboundEmailReceived;
use App\Events\TicketAssigned;
use App\Events\TicketCreated;
use App\Events\TicketStatusChanged;
use App\Listeners\LogTicketHistory;
use App\Listeners\ProcessInboundEmail;
use App\Listeners\SendCommentAddedNotification;
use App\Listeners\SendTicketAssignedNotification;
use App\Listeners\SendTicketCreatedNotification;
use App\Listeners\SendTicketStatusChangedNotification;
use App\Services\AttachmentService;
use App\Services\BusinessHoursService;
use App\Services\CommentService;
use App\Services\DepartmentService;
use App\Services\InboundEmailService;
use App\Services\TicketService;
use App\Support\ServiceDeskManager;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class ServiceDeskServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TicketService::class);
        $this->app->singleton(CommentService::class);
        $this->app->singleton(DepartmentService::class);
        $this->app->singleton(AttachmentService::class);
        $this->app->singleton(InboundEmailService::class);

        $this->app->bind(SlaCalculator::class, BusinessHoursService::class);

        $this->app->singleton(ServiceDeskManager::class, function ($app) {
            return new ServiceDeskManager(
                $app->make(TicketService::class),
                $app->make(CommentService::class),
                $app->make(DepartmentService::class),
                $app->make(AttachmentService::class),
            );
        });
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(lang_path('service-desk'), 'service-desk');
        $this->loadTranslationsFrom(lang_path('filament-service-desk'), 'filament-service-desk');
        $this->loadViewsFrom(resource_path('views/vendor/filament-service-desk'), 'filament-service-desk');
        $this->loadRoutesFrom(base_path('routes/service-desk-webhooks.php'));

        FilamentAsset::register([
            Css::make('filament-service-desk', resource_path('css/filament-service-desk.css')),
        ], 'jeffersongoncalves/filament-service-desk');

        $this->commands([
            CheckSlaBreachesCommand::class,
            CleanInboundEmailsCommand::class,
            CloseStaleTicketsCommand::class,
            PollImapMailboxCommand::class,
            ProcessEscalationsCommand::class,
            RecalculateSlaCommand::class,
        ]);

        if (config('service-desk.register_default_listeners', true)) {
            Event::subscribe(LogTicketHistory::class);

            Event::listen(TicketCreated::class, SendTicketCreatedNotification::class);
            Event::listen(TicketStatusChanged::class, SendTicketStatusChangedNotification::class);
            Event::listen(CommentAdded::class, SendCommentAddedNotification::class);
            Event::listen(TicketAssigned::class, SendTicketAssignedNotification::class);

            Event::listen(InboundEmailReceived::class, ProcessInboundEmail::class);
        }
    }
}
