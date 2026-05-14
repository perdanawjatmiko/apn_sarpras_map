<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule::command('koperasi:import-spreadsheet')
//     ->dailyAt('01:50')
//     ->withoutOverlapping()
//     ->appendOutputTo(storage_path('logs/koperasi-import.log'));

// Schedule::command('sarpras-koperasi:import-spreadsheet')
//     ->dailyAt('02:00')
//     ->withoutOverlapping()
//     ->appendOutputTo(storage_path('logs/sarpras-koperasi-import.log'));
