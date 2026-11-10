<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tandai kantong kedaluwarsa tiap jam (saat pengembangan: "php artisan schedule:work")
Schedule::command('bloodconnect:tandai-kedaluwarsa')->hourly()->withoutOverlapping();
