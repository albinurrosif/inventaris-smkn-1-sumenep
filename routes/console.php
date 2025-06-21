<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Jadwalkan command di sini
Schedule::command('check:overdue-peminjaman')->dailyAt('00:00');
// Atau untuk testing:
//Schedule::command('check:overdue-peminjaman')->everyMinute();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();
