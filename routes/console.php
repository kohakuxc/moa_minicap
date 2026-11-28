<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Automated Recall & CRM: Run daily at 08:00 to flag patients due for annual exams or contact lens refills
Schedule::command('clinic:send-recalls')->dailyAt('08:00');
Schedule::command('registrations:prune')->hourly();

