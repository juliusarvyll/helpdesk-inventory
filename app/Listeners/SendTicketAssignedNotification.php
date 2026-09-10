<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TicketAssigned;
use App\Notifications\TicketAssignedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTicketAssignedNotification implements ShouldQueue
{
    public function handle(TicketAssigned $event): void
    {
        if (! config('service-desk.notifications.notify_on.ticket_assigned', true)) {
            return;
        }

        $assignedTo = $event->assignedTo;

        /** @phpstan-ignore booleanNot.alwaysFalse */
        if (! $assignedTo) {
            return;
        }

        if (method_exists($assignedTo, 'notify')) {
            $assignedTo->notify(new TicketAssignedNotification($event->ticket));
        }
    }
}
