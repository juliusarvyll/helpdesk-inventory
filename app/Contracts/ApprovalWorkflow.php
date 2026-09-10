<?php

namespace App\Contracts;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestApproval;

interface ApprovalWorkflow
{
    public function createApprovalSteps(ServiceRequest $request): void;

    public function processDecision(ServiceRequestApproval $approval, string $decision, ?string $comment = null): void;
}
