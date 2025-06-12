<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Добавь эту строку
Schedule::command('test:minute')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
