<?php

namespace App\Contracts;

use App\Models\EmailChannel;

interface EmailDriver
{
    public function poll(EmailChannel $channel): array;

    public function getDriverName(): string;
}
