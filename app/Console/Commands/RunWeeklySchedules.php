<?php

namespace App\Console\Commands;

use App\Models\WeeklySchedule;
use App\Services\OpenWeeklySchedule;
use Illuminate\Console\Command;

class RunWeeklySchedules extends Command
{
    protected $signature = 'rintis:open-weekly';

    protected $description = 'Open due weekly sessions once per day';

    public function handle(OpenWeeklySchedule $open): int
    {
        $deadline = microtime(true) + 45;
        foreach (WeeklySchedule::where('enabled', true)->lazyById(50) as $schedule) {
            if (microtime(true) >= $deadline) {
                break;
            } $open->handle($schedule);
        }

        return self::SUCCESS;
    }
}
