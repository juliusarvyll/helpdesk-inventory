<?php

namespace App\Concerns;

use App\Models\SlaPolicy;
use App\Services\SlaService;

trait HasSla
{
    public function applySla(?SlaPolicy $policy = null): void
    {
        /** @var SlaService $slaService */
        $slaService = app(SlaService::class);

        $slaService->applyPolicy($this, $policy);
    }
}
