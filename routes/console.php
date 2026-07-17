<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:backup-leads')
    ->dailyAt('03:10')
    ->onOneServer()
    ->withoutOverlapping();

Schedule::command('app:cleanup-funnel-media --dry-run')
    ->dailyAt('03:40')
    ->onOneServer()
    ->withoutOverlapping();

Schedule::command('app:cleanup-funnel-media --older-than-days=14')
    ->weeklyOn(0, '04:10')
    ->onOneServer()
    ->withoutOverlapping();

Schedule::command('app:monitor-funnel-health')
    ->hourly()
    ->onOneServer()
    ->withoutOverlapping();
