<?php

namespace App\Services\Implementations\Auth;

use App\Contracts\Auth\UserRepositoryInterface;
use App\Models\User;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function create(array $data): object
    {
        return User::create($data);
    }

    public function findByEmail(string $email): ?object
    {
        return User::where('email', $email)->first();
    }

    public function update(object $user, array $data): bool
    {
        return $user->update($data);
    }
}
