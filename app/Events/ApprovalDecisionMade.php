<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ServiceRequestApproval;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApprovalDecisionMade
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly ServiceRequestApproval $approval,
    ) {}
}
