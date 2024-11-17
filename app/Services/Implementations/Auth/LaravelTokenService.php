<?php

namespace App\Services\Implementations\Auth;

use App\Contracts\Auth\TokenServiceInterface;
use Illuminate\Support\Facades\Password;

class LaravelTokenService implements TokenServiceInterface
{
    public function create(object $user, string $name): string
    {
        return $user->createToken($name)->plainTextToken;
    }

    public function createPasswordResetToken(mixed $user): string
    {
        return Password::createToken($user);
    }
}
