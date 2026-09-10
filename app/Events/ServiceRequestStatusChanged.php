<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\ServiceRequestStatus;
use App\Models\ServiceRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServiceRequestStatusChanged
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly ServiceRequest $serviceRequest,
        public readonly ServiceRequestStatus $oldStatus,
        public readonly ServiceRequestStatus $newStatus,
    ) {}
}
