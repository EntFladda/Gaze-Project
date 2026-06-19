<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::call(function () {
    Student::query()->update(['weekly_score' => 0]);
    info('✅ Monthly reset: weekly scores have been reset!');
})->monthlyOn(15, '00:00');
