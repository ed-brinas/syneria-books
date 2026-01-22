<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// --- AUTO REVERSE ---
Schedule::command('accounting:auto-reverse')
    ->dailyAt('01:00')       // Run at 1 AM
    ->withoutOverlapping()   // Prevent multiple instances running at once
    ->onOneServer();         // If you have multiple servers, run on only one