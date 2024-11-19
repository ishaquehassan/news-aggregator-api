<?php

namespace App\Providers;

use App\Contracts\Articles\ArticleRepositoryInterface;
use App\Contracts\UserPreferences\UserPreferenceRepositoryInterface;
use App\Services\Implementations\Articles\ArticleRepository;
use App\Services\Implementations\UserPreferences\UserPreferenceRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ArticleRepositoryInterface::class, ArticleRepository::class);
        $this->app->bind(UserPreferenceRepositoryInterface::class, UserPreferenceRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
