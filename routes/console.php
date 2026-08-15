<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('hrm:morning-reminder')->weekdays()->dailyAt('09:00');
Schedule::command('hrm:evening-reminder')->weekdays()->dailyAt('18:30');
Schedule::command('hrm:celebrations')->dailyAt('09:05');
