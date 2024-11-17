<?php

namespace App\Contracts\Auth;

interface PasswordResetRepositoryInterface
{
    public function storeToken(string $email, string $token): void;

    public function findToken(string $email, string $token): ?object;

    public function deleteToken(string $email): void;
}
