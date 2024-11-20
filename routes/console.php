<?php

use App\Jobs\News\NewsAPIJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


Schedule::call(function() {
    foreach (array_keys(config('services.news_apis.services', [])) as $service) {
        foreach (config('services.news_apis.categories', []) as $category) {
            dispatch(new NewsAPIJob($service, $category));
        }
    }
})
    ->description('Fetch news articles from various APIs')
    ->daily()
    ->at('00:00')
    ->withoutOverlapping()
    ->onFailure(fn () => logger()->error('Failed to fetch news articles'));
