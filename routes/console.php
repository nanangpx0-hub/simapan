<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pemantauan SLA/deadline pelaporan SUSENAS-SERUTI harian.
Schedule::command('reporting:monitor-deadlines')->dailyAt('07:30');
