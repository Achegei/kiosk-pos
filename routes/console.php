<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 🔥 ADD THIS BELOW
Schedule::command('system:backup')
    ->dailyAt('02:00')
    ->withoutOverlapping();