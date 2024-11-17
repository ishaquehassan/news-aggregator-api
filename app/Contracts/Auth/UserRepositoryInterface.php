<?php

namespace App\Contracts\Auth;

interface UserRepositoryInterface
{
    public function create(array $data): object;

    public function findByEmail(string $email): ?object;

    public function update(object $user, array $data): bool;
}
