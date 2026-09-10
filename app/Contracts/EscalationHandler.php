<?php

namespace App\Contracts;

use App\Models\EscalationRule;
use App\Models\Ticket;

interface EscalationHandler
{
    public function handle(EscalationRule $rule, Ticket $ticket): void;
}
