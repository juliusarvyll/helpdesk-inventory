<?php

namespace App\Contracts;

use App\Models\BusinessHoursSchedule;
use Carbon\Carbon;

interface SlaCalculator
{
    public function calculateDueDate(Carbon $startTime, int $minutes, ?BusinessHoursSchedule $schedule = null): Carbon;
}
