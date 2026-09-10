<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Notifications\TicketCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTicketCreatedNotification implements ShouldQueue
{
    public function handle(TicketCreated $event): void
    {
        if (! config('service-desk.notifications.notify_on.ticket_created', true)) {
            return;
        }

        $ticket = $event->ticket;
        $user = $ticket->user;

        if (! $user) {
            return;
        }

        if (method_exists($user, 'notify')) {
            $user->notify(new TicketCreatedNotification($ticket));
        }
    }
}
