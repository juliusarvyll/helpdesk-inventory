<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\InboundEmail;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InboundEmailReceived
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly InboundEmail $inboundEmail,
    ) {}
}
