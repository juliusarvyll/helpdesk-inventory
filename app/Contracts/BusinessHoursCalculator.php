<?php

namespace App\Contracts;

use App\Models\BusinessHoursSchedule;
use Carbon\Carbon;

interface BusinessHoursCalculator
{
    public function addBusinessMinutes(Carbon $start, int $minutes, BusinessHoursSchedule $schedule): Carbon;

    public function isBusinessHour(Carbon $dateTime, BusinessHoursSchedule $schedule): bool;

    public function isHoliday(Carbon $date, BusinessHoursSchedule $schedule): bool;
}
