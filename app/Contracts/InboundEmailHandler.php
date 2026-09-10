<?php

namespace App\Contracts;

use App\Models\InboundEmail;

interface InboundEmailHandler
{
    public function handle(InboundEmail $inboundEmail): void;
}
