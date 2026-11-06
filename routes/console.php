<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('rintis:expire-qris')->everyMinute()->withoutOverlapping(30);

Schedule::command('rintis:open-weekly')->everyMinute()->withoutOverlapping(5);
