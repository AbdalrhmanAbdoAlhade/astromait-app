<?php

use Illuminate\Support\Facades\Schedule; // 1. أضف هذا السطر في أعلى الملف
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 2. غيّر السطر من $schedule إلى Schedule::
Schedule::command('auctions:process')->everyMinute();
