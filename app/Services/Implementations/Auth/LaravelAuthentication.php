<?php

namespace App\Services\Implementations\Auth;

use App\Contracts\Auth\AuthenticationInterface;
use Illuminate\Support\Facades\Auth;


class LaravelAuthentication implements AuthenticationInterface
{
    public function attempt(array $credentials): bool
    {
        return Auth::attempt($credentials);
    }

    public function user(): ?object
    {
        return Auth::user();
    }
}
