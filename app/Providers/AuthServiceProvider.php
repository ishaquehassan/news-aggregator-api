<?php

namespace App\Providers;

use App\Contracts\Auth\AuthenticationInterface;
use App\Contracts\Auth\HashServiceInterface;
use App\Contracts\Auth\PasswordResetRepositoryInterface;
use App\Contracts\Auth\TokenServiceInterface;
use App\Contracts\Auth\UserRepositoryInterface;
use App\Services\Implementations\Auth\EloquentUserRepository;
use App\Services\Implementations\Auth\LaravelAuthentication;
use App\Services\Implementations\Auth\LaravelHashService;
use App\Services\Implementations\Auth\LaravelPasswordResetRepository;
use App\Services\Implementations\Auth\LaravelTokenService;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(HashServiceInterface::class, LaravelHashService::class);
        $this->app->bind(TokenServiceInterface::class, LaravelTokenService::class);
        $this->app->bind(PasswordResetRepositoryInterface::class, LaravelPasswordResetRepository::class);
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(AuthenticationInterface::class, LaravelAuthentication::class);
    }
}
