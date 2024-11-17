<?php

namespace App\Contracts\Auth;
interface AuthenticationInterface
{
    public function attempt(array $credentials): bool;

    public function user(): ?object;
}
