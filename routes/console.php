<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('koperasi:import-spreadsheet')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/koperasi-import.log'));

Schedule::command('sarpras-koperasi:import-spreadsheet --url')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/sarpras-koperasi-import.log'));
