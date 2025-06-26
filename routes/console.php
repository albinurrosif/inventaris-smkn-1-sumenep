<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Jadwalkan command di sini
//Schedule::command('check:overdue-peminjaman')->twiceDaily(8, 20);


Schedule::command('peminjaman:check-status')->dailyAt('04:00');


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();
