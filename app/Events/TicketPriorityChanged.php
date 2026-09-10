<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\TicketPriority;
use App\Models\Ticket;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketPriorityChanged
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly TicketPriority $oldPriority,
        public readonly TicketPriority $newPriority,
        public readonly ?object $performer = null,
    ) {}
}
