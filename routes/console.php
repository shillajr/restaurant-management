<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\LoyverseService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled Tasks
Schedule::call(function () {
    app(LoyverseService::class)->syncDailySales(now()->format('Y-m-d'));
})->dailyAt('02:00')->name('sync-loyverse-sales')->withoutOverlapping();

Schedule::command('approvals:remind')->dailyAt('09:00')->name('remind-pending-approvals');

Schedule::command('ledgers:send-vendor-reminders')->dailyAt('08:00')->name('send-vendor-reminders')->withoutOverlapping();
