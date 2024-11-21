<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


Schedule::command('articles:fetch --all')
    ->description('Fetch news articles from various APIs')
    ->daily()
    ->at('00:00')
    ->withoutOverlapping()
    ->onFailure(fn () => logger()->error('Failed to fetch news articles'))
    ->runInBackground();
