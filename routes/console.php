<?php

use App\Payments\DueService;
use App\Payments\PaymentService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('dues:generate {--month= : Month as YYYY-MM (default: this month)}', function (DueService $dues): int {
    $option = $this->option('month');

    if (is_string($option) && $option !== '' && ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $option)) {
        $this->error('Give the month as YYYY-MM, for example 2026-11.');

        return 1;
    }

    $month = (is_string($option) && $option !== '' ? Carbon::createFromFormat('!Y-m', $option) : null) ?? now()->startOfMonth();
    $added = $dues->generate($month);

    $this->info("Dues for {$month->format('F Y')}: {$added} families added (families that already had this month were skipped).");

    return 0;
})->purpose('Add the monthly due for every active family (safe to run more than once)');

Artisan::command('payments:expire {--minutes= : Minutes after which an unfinished checkout expires}', function (PaymentService $payments): int {
    $option = $this->option('minutes');
    $minutes = is_numeric($option) ? (int) $option : (int) config('services.razorpay.expire_after_minutes', 30);
    $expired = $payments->expireStale(max(5, $minutes));

    $this->info("{$expired} unfinished checkouts marked expired.");

    return 0;
})->purpose('Mark checkouts that were started but never paid as expired');

// Run by `php artisan schedule:run` every minute (cron) on the server.
Schedule::command('dues:generate')->monthlyOn(1, '06:00')->withoutOverlapping();
Schedule::command('payments:expire')->everyFiveMinutes()->withoutOverlapping();
