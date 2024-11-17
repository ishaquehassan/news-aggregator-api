<?php

namespace App\Contracts\Auth;

interface TokenServiceInterface
{
    public function create(object $user, string $name): string;

    public function createPasswordResetToken(object $user): string;
}
